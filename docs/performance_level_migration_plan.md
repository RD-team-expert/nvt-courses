# Performance Level Migration Plan

## Overview

This document outlines the strategy for migrating from the monetary incentive system to the new performance level-based evaluation system. The migration will be executed in phases to ensure a smooth transition while maintaining backward compatibility.

## Implementation Status

- ✅ Created migration to add `performance_level` field to Incentives table
- ✅ Updated Incentive model to include `performance_level` field
- ✅ Updated Evaluation and EvaluationHistory controllers to use performance levels
- ✅ Enhanced UserEvaluation and OnlineCourseEvaluation controllers with performance data
- ✅ Updated email services to include performance level information
- ✅ Added performance level dropdowns and badges to Admin UI
- ✅ Ensured backward compatibility during transition period

## Data Migration Plan

### Phase 1: Prepare Database (Day 1)

1. Run the migration to add the `performance_level` field to the `incentives` table:
   ```bash
   php artisan migrate
   ```

2. Execute the `MapEvaluationsToPerformanceLevels` command with the dry-run option first to validate:
   ```bash
   php artisan evaluations:map-performance-levels --dry-run
   ```

3. Review the exported CSV file containing flagged records that need manual review.

4. Run the actual migration command to populate performance levels:
   ```bash
   php artisan evaluations:map-performance-levels
   ```

### Phase 2: Update Incentive Configuration (Day 2-3)

1. Access the admin configuration panel and set up performance levels for each incentive tier.
2. For each incentive record:
   - Map existing score ranges to appropriate performance levels
   - Verify that incentive amounts align with performance levels
   - Configure level, tier, and performance level combinations

3. Test the configuration by creating test evaluations and verifying that performance levels are correctly assigned.

### Phase 3: User Interface Transition (Day 4-7)

1. Update all frontend components to display performance level badges and information:
   - Evaluation forms now show performance levels with their visual styling
   - Reports include performance level data
   - Email notifications display performance level badges

2. Maintain incentive amount displays alongside performance levels during initial transition.

3. Gradually emphasize performance levels over monetary amounts in the UI.

### Phase 4: Final Migration and Monitoring (Day 8-14)

1. Run final validation checks to ensure all evaluations have performance levels assigned:
   ```bash
   # Query to find any evaluations missing performance level data
   SELECT id, user_id, total_score FROM evaluations WHERE performance_level IS NULL;
   ```

2. Address any remaining records with missing performance levels.

3. Monitor system usage for any issues or feedback.

4. Make UI adjustments based on user feedback.

## Technical Details

### Database Compatibility

- Both `incentive_amount` and `performance_level` fields will be maintained in parallel.
- API responses include both fields to support frontends during transition.
- Performance level will be the primary source of truth, with incentive amounts derived from it.

### Code Changes

1. Controllers now map scores to performance levels before saving.
2. Frontend components display color-coded badges based on performance level.
3. Email templates include performance level information and styling.
4. Configuration UI allows setting performance levels directly.

### Performance Level Mapping

| Performance Level | Label | Score Range | Color | Visual Style |
|------------------|-------|-------------|-------|--------------|
| 1 | Outstanding | 13-15 | Green | Badge with success styling |
| 2 | Reliable | 10-12 | Blue | Badge with info styling |
| 3 | Developing | 7-9 | Yellow | Badge with warning styling |
| 4 | Underperforming | 0-6 | Red | Badge with danger styling |

## Rollback Plan

If critical issues are encountered during migration:

1. Keep dual fields active (incentive_amount and performance_level).
2. If necessary, temporarily revert UI to emphasize monetary amounts.
3. Fix issues in the performance level implementation.
4. Retry migration with fixes applied.

## Timeline

- **Week 1**: Complete data migration and configuration
- **Week 2**: UI transition and user communication
- **Week 3**: Final validation and monitoring
- **Week 4**: Complete transition, with monetary amounts shown only as reference

## Success Metrics

The migration will be considered successful when:

1. All evaluations have appropriate performance levels assigned
2. UI consistently displays performance level information
3. Users understand and can effectively use the new system
4. Performance level becomes the primary metric for evaluation feedback

## Training and Communication

1. Prepare documentation for managers explaining the new performance level system
2. Create visual guides showing how to interpret performance level badges
3. Send notification emails about the transition, highlighting benefits
4. Schedule optional training sessions for managers to learn the new system