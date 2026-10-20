import type { BranchScope } from '@/types/master-data';

/** "All branches", the branch names, or a dash when unknown. */
export function branchScopeLabel(scope: BranchScope): string {
    if (scope.all === true) {
        return 'All branches';
    }

    if (scope.all === false) {
        return scope.names.length === 0 ? '—' : scope.names.join(', ');
    }

    return '—';
}
