import assert from 'node:assert/strict';
import test from 'node:test';

import { compareSetupValues, setupComparison } from '../../resources/js/setup-comparison.js';

const definitions = {
    tyre_pressure_fl: { label: 'Tyre pressure FL', unit: 'bar', group: 'tyres' },
    caster_front_deg: { label: 'Front caster', unit: 'deg', group: 'chassis' },
    pinion_teeth: { label: 'Pinion teeth', unit: '', group: 'drivetrain' },
};

test('compares only configured parameters, normalizes numeric values and highlights real changes', () => {
    const rows = compareSetupValues(definitions,
        { tyre_pressure_fl: '0.80', caster_front_deg: 9 },
        { tyre_pressure_fl: 0.8, caster_front_deg: '10.5', pinion_teeth: '11' });

    assert.deepEqual(rows.map((row) => [row.key, row.changed]), [
        ['tyre_pressure_fl', false],
        ['caster_front_deg', true],
        ['pinion_teeth', true],
    ]);
    assert.equal(rows[2].before, null);
    assert.equal(rows[2].after, '11');
});

test('keeps missing fields different from zero and ignores fields outside the shared definitions', () => {
    const rows = compareSetupValues(definitions, {}, { pinion_teeth: 0, mystery_field: 9 });

    assert.equal(rows.length, 1);
    assert.equal(rows[0].key, 'pinion_teeth');
    assert.equal(rows[0].changed, true);
});

test('only permits comparing two distinct setups from the same vehicle', () => {
    const comparison = setupComparison([
        { id: 1, vehicle_id: 8, vehicle_name: 'Kart 8', name: 'Dry', values: { caster_front_deg: 9 } },
        { id: 2, vehicle_id: 8, vehicle_name: 'Kart 8', name: 'Wet', values: { caster_front_deg: 10 } },
        { id: 3, vehicle_id: 9, vehicle_name: 'Kart 9', name: 'Other', values: { caster_front_deg: 12 } },
    ], definitions, { chassis: 'Chassis', tyres: 'Tyres', drivetrain: 'Drivetrain' }, { notSet: 'Not set' });

    comparison.init();
    assert.equal(comparison.canCompare, true);
    assert.equal(comparison.changedCount, 1);
    assert.equal(comparison.groups[0].key, 'chassis');

    comparison.afterId = '3';
    assert.equal(comparison.canCompare, false);
    assert.deepEqual(comparison.allRows, []);

    comparison.vehicleId = '9';
    comparison.resetSelection();
    assert.equal(comparison.canCompare, false);
    assert.equal(comparison.availableSetups.length, 1);
});
