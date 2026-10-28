const comparableValue = (value) => {
    if (value === undefined || value === null || value === '') {
        return null;
    }

    const text = String(value).trim();

    if (/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/.test(text)) {
        return Number(text);
    }

    return text;
};

export function compareSetupValues(definitions, before = {}, after = {}) {
    return Object.entries(definitions)
        .filter(([key]) => comparableValue(before[key]) !== null || comparableValue(after[key]) !== null)
        .map(([key, definition]) => ({
            key,
            label: definition.label,
            unit: definition.unit,
            group: definition.group,
            before: before[key] ?? null,
            after: after[key] ?? null,
            changed: comparableValue(before[key]) !== comparableValue(after[key]),
        }));
}

export function setupComparison(setups, definitions, groupLabels, labels) {
    return {
        setups,
        definitions,
        groupLabels,
        labels,
        vehicleId: String(setups[0]?.vehicle_id ?? ''),
        beforeId: '',
        afterId: '',
        onlyChanges: true,

        init() {
            this.resetSelection();
        },

        get vehicles() {
            return [...new Map(this.setups.map((setup) => [String(setup.vehicle_id), {
                id: String(setup.vehicle_id),
                name: setup.vehicle_name,
            }])).values()];
        },

        get availableSetups() {
            return this.setups.filter((setup) => String(setup.vehicle_id) === this.vehicleId);
        },

        resetSelection() {
            this.beforeId = String(this.availableSetups[0]?.id ?? '');
            this.afterId = String(this.availableSetups[1]?.id ?? '');
        },

        get beforeSetup() {
            return this.availableSetups.find((setup) => String(setup.id) === this.beforeId);
        },

        get afterSetup() {
            return this.availableSetups.find((setup) => String(setup.id) === this.afterId);
        },

        get canCompare() {
            return Boolean(this.beforeSetup && this.afterSetup && this.beforeId !== this.afterId);
        },

        get allRows() {
            if (!this.canCompare) {
                return [];
            }

            return compareSetupValues(this.definitions, this.beforeSetup.values ?? {}, this.afterSetup.values ?? {});
        },

        get changedCount() {
            return this.allRows.filter((row) => row.changed).length;
        },

        get groups() {
            return Object.entries(this.groupLabels)
                .map(([key, label]) => ({
                    key,
                    label,
                    rows: this.allRows.filter((row) => row.group === key && (!this.onlyChanges || row.changed)),
                }))
                .filter((group) => group.rows.length > 0);
        },

        display(value, unit) {
            if (comparableValue(value) === null) {
                return this.labels.notSet;
            }

            return `${value}${unit ? ` ${unit}` : ''}`;
        },
    };
}

if (typeof window !== 'undefined') {
    window.PitMetricSetupComparison = setupComparison;
}
