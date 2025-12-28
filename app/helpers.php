<?php

use App\Services\BranchContext;

if (!function_exists('current_branch_id')) {
    /**
     * Get the current branch ID from the branch context.
     */
    function current_branch_id(): ?int
    {
        return app(BranchContext::class)->getCurrentBranchId();
    }
}

if (!function_exists('current_branch')) {
    /**
     * Get the current branch model from the branch context.
     */
    function current_branch(): ?\App\Models\Branch
    {
        return app(BranchContext::class)->getCurrentBranch();
    }
}

if (!function_exists('branch_context')) {
    /**
     * Get the branch context service instance.
     */
    function branch_context(): BranchContext
    {
        return app(BranchContext::class);
    }
}

if (!function_exists('app_currency')) {
    /**
     * Resolve the active currency code for the current context.
     */
    function app_currency(): string
    {
        $branch_currency = current_branch()?->currency;

        return $branch_currency ?: config('app.currency', 'TZS');
    }
}

if (!function_exists('money')) {
    /**
     * Format a money amount with the active currency.
     */
    function money(?float $amount, ?string $currency = null): string
    {
        if ($amount === null) {
            return '-';
        }

        $currency_code = strtoupper($currency ?: app_currency());

        return $currency_code . ' ' . number_format($amount, 2, '.', ',');
    }
}

