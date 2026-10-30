export const session = { authenticated: true, cloudEnabled: true, canWrite: true,
  user: { id: 12, name: 'PN Rent', email: 'pn@example.test' }, workspace: { id: 50, name: 'PN Rent' } };
export const snapshot = { workspace: session.workspace, vehicles: [{ id: 4, name: 'Kart PN', category: 'kart', status: 'active' }],
  events: [], configurations: [], setups: [], maintenance_schedules: [], work_orders: [], setup_fields: {}, drivers: [], timing_laps: [],
  sessions: [{ id: 7, vehicle_id: 4, vehicle: 'Kart PN', session_type: 'practice', started_at: '2026-10-30T09:00:00Z', status: 'draft' }],
};
