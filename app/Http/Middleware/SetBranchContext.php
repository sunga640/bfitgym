<?php

namespace App\Http\Middleware;

use App\Services\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetBranchContext
{
    public function __construct(
        protected BranchContext $branch_context
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Initialize branch context for authenticated users
        if ($request->user()) {
            $this->branch_context->initializeOnLogin($request->user());
        }

        // Check if user wants to switch branch via query parameter
        if ($request->has('switch_branch') && $request->user()) {
            $branch_id = (int) $request->input('switch_branch');
            
            if ($this->branch_context->canSwitchBranches($request->user())) {
                $this->branch_context->setCurrentBranch($branch_id);
                
                // Redirect to remove the query parameter
                return redirect()->to($request->url());
            }
        }

        // Check if user wants to clear branch selection
        if ($request->has('clear_branch') && $request->user()) {
            $this->branch_context->clearCurrentBranch();
            
            return redirect()->to($request->url());
        }

        return $next($request);
    }
}

