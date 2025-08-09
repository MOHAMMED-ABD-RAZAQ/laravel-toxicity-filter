<?php

namespace Packages\ToxicityFilter\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Packages\ToxicityFilter\Contracts\ToxicityFilterInterface;
use Packages\ToxicityFilter\Exceptions\ToxicityFilterException;

class ToxicityFilterMiddleware
{
    public function __construct(
        private ToxicityFilterInterface $toxicityFilter
    ) {}

    public function handle(Request $request, Closure $next, ...$parameters): Response
    {
        $config = config('toxicity-filter');
        
        // Check if user should bypass toxicity filtering
        if ($this->shouldBypass($request, $config)) {
            return $next($request);
        }

        // Extract content from request based on configuration
        $content = $this->extractContent($request, $parameters);
        
        if (empty($content)) {
            return $next($request);
        }

        try {
            // Analyze content for toxicity
            $result = $this->toxicityFilter->analyze($content);
            
            // Check if content should be blocked
            if ($this->shouldBlockContent($result, $config)) {
                return $this->createBlockedResponse($config);
            }
            
            // Check if content should be flagged
            if ($this->shouldFlagContent($result, $config)) {
                $this->flagContent($request, $result);
            }
            
            // Check if content should trigger a warning
            if ($this->shouldWarnContent($result, $config)) {
                $this->addWarningToRequest($request, $config);
            }
            
            // Add toxicity result to request for further processing
            $request->merge(['_toxicity_result' => $result]);
            
        } catch (ToxicityFilterException $e) {
            // Log the error and continue (fail-safe approach)
            \Log::error('Toxicity filter error: ' . $e->getMessage());
        }

        return $next($request);
    }

    private function shouldBypass(Request $request, array $config): bool
    {
        $bypassConfig = $config['bypass'] ?? [];
        
        // Bypass for admin users
        if (($bypassConfig['admin_users'] ?? false) && Auth::check()) {
            $user = Auth::user();
            $trustedRoles = $bypassConfig['trusted_user_roles'] ?? [];
            
            if (method_exists($user, 'hasRole')) {
                foreach ($trustedRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function extractContent(Request $request, array $parameters): string
    {
        $content = '';
        
        // If specific fields are provided in middleware parameters
        if (!empty($parameters)) {
            foreach ($parameters as $field) {
                $fieldContent = $request->input($field, '');
                if (!empty($fieldContent)) {
                    $content .= $fieldContent . ' ';
                }
            }
        } else {
            // Default behavior: check common content fields
            $contentFields = ['content', 'message', 'comment', 'post', 'text', 'body'];
            
            foreach ($contentFields as $field) {
                $fieldContent = $request->input($field, '');
                if (!empty($fieldContent)) {
                    $content .= $fieldContent . ' ';
                }
            }
        }

        return trim($content);
    }

    private function shouldBlockContent($result, array $config): bool
    {
        $blockConfig = $config['actions']['block'] ?? [];
        if (!($blockConfig['enabled'] ?? false)) {
            return false;
        }

        $threshold = $config['thresholds']['block'] ?? 0.8;
        return $result->shouldBlock($threshold);
    }

    private function shouldFlagContent($result, array $config): bool
    {
        $flagConfig = $config['actions']['flag'] ?? [];
        if (!($flagConfig['enabled'] ?? false)) {
            return false;
        }

        $threshold = $config['thresholds']['flag'] ?? 0.6;
        return $result->shouldFlag($threshold);
    }

    private function shouldWarnContent($result, array $config): bool
    {
        $warnConfig = $config['actions']['warn'] ?? [];
        if (!($warnConfig['enabled'] ?? false)) {
            return false;
        }

        $threshold = $config['thresholds']['warn'] ?? 0.4;
        return $result->shouldWarn($threshold);
    }

    private function createBlockedResponse(array $config): Response
    {
        $blockConfig = $config['actions']['block'] ?? [];
        $message = $blockConfig['message'] ?? 'Your content has been blocked due to inappropriate language.';
        $httpStatus = $blockConfig['http_status'] ?? 422;

        return response()->json([
            'error' => 'Content blocked',
            'message' => $message,
        ], $httpStatus);
    }

    private function flagContent(Request $request, $result): void
    {
        // Here you could implement flagging logic, such as:
        // - Storing flagged content in database
        // - Sending notification to admins
        // - Adding to moderation queue
        
        \Log::warning('Content flagged for review', [
            'url' => $request->url(),
            'user_id' => Auth::id(),
            'toxicity_score' => $result->getToxicityScore(),
            'categories' => $result->getCategories(),
        ]);
    }

    private function addWarningToRequest(Request $request, array $config): void
    {
        $warnConfig = $config['actions']['warn'] ?? [];
        $message = $warnConfig['message'] ?? 'Please review your content for appropriate language.';
        
        // Add warning to request attributes for the application to handle
        $request->attributes->set('toxicity_warning', $message);
    }
}
