export function memoryStorage() {
  const values = new Map();
  return { getItem: key => values.get(key) ?? null, setItem: (key, value) => values.set(key, value), removeItem: key => values.delete(key) };
}

export function memoryIndexedDB() {
  const databases = new Map();
  return {
    open(name) {
      const request = {};
      queueMicrotask(() => {
        const upgrade = !databases.has(name);
        if (upgrade) {
          const stores = new Map();
          databases.set(name, {
            objectStoreNames: { contains: key => stores.has(key) },
            createObjectStore(key, options) { stores.set(key, { keyPath: options.keyPath, data: new Map() }); },
            transaction(names, mode) {
              const selected = typeof names === 'string' ? [names] : names;
              const copies = new Map(selected.map(key => [key, structuredClone(stores.get(key))]));
              let pending = 0, finished = false;
              const tx = { error: null, oncomplete: null, onerror: null, onabort: null };
              const enqueue = action => {
                const item = {};
                pending++;
                queueMicrotask(() => {
                  if (finished) return;
                  try {
                    item.result = action();
                    item.onsuccess?.();
                  } catch (error) {
                    finished = true; tx.error = error; tx.onerror?.(); tx.onabort?.();
                  }
                  pending--;
                  if (!pending && !finished) setTimeout(() => {
                    if (pending || finished) return;
                    finished = true;
                    if (mode === 'readwrite') for (const [key, value] of copies) stores.set(key, value);
                    tx.oncomplete?.();
                  }, 0);
                });
                return item;
              };
              tx.objectStore = name => {
                const store = copies.get(name);
                if (!store) throw new Error('Store not in transaction');
                const write = (entry, replace) => enqueue(() => {
                  if (mode !== 'readwrite') throw new Error('Read only');
                  const id = entry[store.keyPath];
                  if (!replace && store.data.has(id)) throw new Error('ConstraintError');
                  store.data.set(id, structuredClone(entry));
                });
                return {
                  get: id => enqueue(() => structuredClone(store.data.get(id))),
                  getAll: () => enqueue(() => structuredClone([...store.data.values()])),
                  count: () => enqueue(() => store.data.size),
                  add: entry => write(entry, false), put: entry => write(entry, true),
                  delete: id => enqueue(() => store.data.delete(id)),
                };
              };
              tx.abort = () => { finished = true; tx.onabort?.(); };
              return tx;
            },
            close() {},
          });
        }
        request.result = databases.get(name);
        if (upgrade) request.onupgradeneeded?.();
        request.onsuccess?.();
      });
      return request;
    },
  };
}
