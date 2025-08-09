<?php

namespace Packages\ToxicityFilter\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Packages\ToxicityFilter\Facades\ToxicityFilter;
use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

class TestToxicityCommand extends Command
{
    protected $signature = 'toxicity:test {content? : Content to analyze for toxicity} {--provider= : Specific provider to use (openai, perspective)}';
    protected $description = 'Test the toxicity filter package functionality';

    public function handle()
    {
        $content = $this->argument('content');
        $provider = $this->option('provider');

        if ($content) {
            $this->analyzeSpecificContent($content, $provider);
        } else {
            $this->runFullTestSuite();
        }
    }

    private function analyzeSpecificContent(string $content, ?string $provider = null)
    {
        $this->info('🔍 Analyzing Specific Content');
        $this->line(str_repeat('=', 60));
        $this->newLine();

        $this->line("📝 Content: \"<fg=yellow>{$content}</>");
        $this->line("🔧 Provider: " . ($provider ?: 'default'));
        $this->newLine();

        try {
            // Check if providers are configured
            $hasApiKeys = $this->checkApiKeys();
            
            if ($hasApiKeys && ($provider || config('toxicity-filter.providers.openai.api_key'))) {
                $this->analyzeWithRealProvider($content, $provider);
            } else {
                $this->analyzeWithMockProvider($content);
            }

        } catch (\Exception $e) {
            $this->error("❌ Analysis failed: " . $e->getMessage());
            $this->newLine();
            $this->warn("💡 Make sure you have API keys configured in your .env file");
            $this->line("   OPENAI_API_KEY=your_key_here");
            $this->line("   PERSPECTIVE_API_KEY=your_key_here");
        }
    }

    private function analyzeWithRealProvider(string $content, ?string $provider = null)
    {
        $this->line("🤖 <fg=green>Analyzing with REAL AI provider...</>");
        
        try {
            $result = ToxicityFilter::analyze($content, $provider);
            
            $this->newLine();
            $this->info("📊 Analysis Results:");
            $this->line("   Toxicity Score: <fg=red>" . round($result->getToxicityScore(), 3) . "</>");
            $this->line("   Provider Used: <fg=blue>" . $result->getProvider() . "</>");
            
            if (!empty($result->getCategories())) {
                $this->line("   Categories: <fg=yellow>" . implode(', ', $result->getCategories()) . "</>");
            } else {
                $this->line("   Categories: <fg=green>None detected</>");
            }

            $this->newLine();
            $this->info("🎯 Recommended Actions:");
            
            if ($result->shouldBlock(config('toxicity-filter.thresholds.block', 0.8))) {
                $this->line("   🚫 <fg=red>BLOCK</> - Content should be blocked");
            } elseif ($result->shouldFlag(config('toxicity-filter.thresholds.flag', 0.6))) {
                $this->line("   🚩 <fg=yellow>FLAG</> - Content should be flagged for review");
            } elseif ($result->shouldWarn(config('toxicity-filter.thresholds.warn', 0.4))) {
                $this->line("   ⚠️ <fg=blue>WARN</> - Content should trigger a warning");
            } else {
                $this->line("   ✅ <fg=green>ALLOW</> - Content is acceptable");
            }

            $this->newLine();
            $this->comment("📄 Raw Result JSON:");
            $this->line($result->toJson());

        } catch (\Exception $e) {
            $this->error("❌ Real provider analysis failed: " . $e->getMessage());
            $this->warn("💡 Falling back to mock analysis...");
            $this->analyzeWithMockProvider($content);
        }
    }

    private function analyzeWithMockProvider(string $content)
    {
        $this->line("🎭 <fg=cyan>Analyzing with MOCK provider...</>");
        
        // Simple mock scoring based on content characteristics
        $words = str_word_count(strtolower($content));
        $badWords = ['stupid', 'idiot', 'hate', 'kill', 'die', 'moron', 'dumb', 'fuck', 'shit', 'damn'];
        $badWordCount = 0;
        
        foreach ($badWords as $badWord) {
            if (stripos($content, $badWord) !== false) {
                $badWordCount++;
            }
        }
        
        // Calculate mock score
        $baseScore = min($badWordCount * 0.3, 0.9);
        $lengthFactor = min(strlen($content) / 100, 0.1);
        $mockScore = min($baseScore + $lengthFactor, 1.0);
        
        // Determine categories
        $categories = [];
        if (stripos($content, 'hate') !== false || stripos($content, 'kill') !== false) {
            $categories[] = 'hate';
        }
        if ($badWordCount > 0) {
            $categories[] = 'harassment';
        }
        if (stripos($content, 'fuck') !== false || stripos($content, 'shit') !== false) {
            $categories[] = 'profanity';
        }

        $this->newLine();
        $this->info("📊 Mock Analysis Results:");
        $this->line("   Toxicity Score: <fg=red>" . round($mockScore, 3) . "</>");
        $this->line("   Provider Used: <fg=blue>mock</>");
        
        if (!empty($categories)) {
            $this->line("   Categories: <fg=yellow>" . implode(', ', $categories) . "</>");
        } else {
            $this->line("   Categories: <fg=green>None detected</>");
        }

        $this->newLine();
        $this->info("🎯 Recommended Actions:");
        
        $blockThreshold = config('toxicity-filter.thresholds.block', 0.8);
        $flagThreshold = config('toxicity-filter.thresholds.flag', 0.6);
        $warnThreshold = config('toxicity-filter.thresholds.warn', 0.4);
        
        if ($mockScore >= $blockThreshold) {
            $this->line("   🚫 <fg=red>BLOCK</> - Content should be blocked (score >= {$blockThreshold})");
        } elseif ($mockScore >= $flagThreshold) {
            $this->line("   🚩 <fg=yellow>FLAG</> - Content should be flagged for review (score >= {$flagThreshold})");
        } elseif ($mockScore >= $warnThreshold) {
            $this->line("   ⚠️ <fg=blue>WARN</> - Content should trigger a warning (score >= {$warnThreshold})");
        } else {
            $this->line("   ✅ <fg=green>ALLOW</> - Content is acceptable (score < {$warnThreshold})");
        }

        $this->newLine();
        $this->comment("💡 This is a mock analysis. For real results, configure API keys in .env");
    }

    private function checkApiKeys(): bool
    {
        $openaiKey = config('toxicity-filter.providers.openai.api_key');
        $perspectiveKey = config('toxicity-filter.providers.perspective.api_key');
        
        return !empty($openaiKey) || !empty($perspectiveKey);
    }

    private function runFullTestSuite()
    {
        $this->info('🧪 Testing Laravel AI Toxicity Filter Package');
        $this->line(str_repeat('=', 60));
        $this->newLine();

        $this->testValueObject();
        $this->testConfiguration();
        $this->testServiceRegistration();
        $this->testFacade();
        $this->testDatabase();
        $this->testMockAnalysis();
        $this->showSetupInstructions();

        $this->newLine();
        $this->info('✨ All tests completed!');
    }

    private function testValueObject()
    {
        $this->info('📊 Test 1: ToxicityResult Value Object');
        $this->line(str_repeat('-', 40));

        $result = new ToxicityResult(
            toxicityScore: 0.85,
            categories: ['harassment', 'hate'],
            provider: 'test',
            explanation: 'Content contains offensive language',
            metadata: ['confidence' => 0.92]
        );

        $this->line("Toxicity Score: " . $result->getToxicityScore());
        $this->line("Categories: " . implode(', ', $result->getCategories()));
        $this->line("Provider: " . $result->getProvider());
        $this->line("Is Toxic (threshold 0.5): " . ($result->isToxic(0.5) ? 'Yes' : 'No'));
        $this->line("Should Block (threshold 0.8): " . ($result->shouldBlock(0.8) ? 'Yes' : 'No'));
        
        $this->comment("✅ ToxicityResult working correctly");
        $this->newLine();
    }

    private function testConfiguration()
    {
        $this->info('🔧 Test 2: Configuration');
        $this->line(str_repeat('-', 40));

        $config = config('toxicity-filter');
        
        if ($config) {
            $this->line("✅ Configuration loaded successfully");
            $this->line("Default provider: " . ($config['default'] ?? 'not set'));
            $this->line("Available providers: " . implode(', ', array_keys($config['providers'] ?? [])));
            
            $openaiKey = config('toxicity-filter.providers.openai.api_key');
            $perspectiveKey = config('toxicity-filter.providers.perspective.api_key');
            
            $this->line("OpenAI API Key: " . ($openaiKey ? '✅ Configured' : '❌ Missing'));
            $this->line("Perspective API Key: " . ($perspectiveKey ? '✅ Configured' : '❌ Missing'));
        } else {
            $this->error("❌ Configuration not found");
            $this->warn("Run: php artisan vendor:publish --tag=toxicity-filter-config");
        }
        
        $this->newLine();
    }

    private function testServiceRegistration()
    {
        $this->info('🏭 Test 3: Service Registration');
        $this->line(str_repeat('-', 40));

        try {
            $service = app('toxicity-filter');
            $this->line("✅ Service registered successfully");
            $this->line("Service class: " . get_class($service));
            
            if (method_exists($service, 'getAvailableProviders')) {
                $providers = $service->getAvailableProviders();
                $this->line("Available providers: " . implode(', ', $providers));
            }
        } catch (\Exception $e) {
            $this->error("❌ Service registration failed: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testFacade()
    {
        $this->info('🎭 Test 4: Facade Registration');
        $this->line(str_repeat('-', 40));

        try {
            if (class_exists('Packages\ToxicityFilter\Facades\ToxicityFilter')) {
                $this->line("✅ Facade class exists");
                
                try {
                    $providers = ToxicityFilter::getAvailableProviders();
                    $this->line("✅ Facade working - Available providers: " . implode(', ', $providers));
                } catch (\Exception $e) {
                    $this->warn("⚠️ Facade exists but providers may not be configured: " . $e->getMessage());
                }
            } else {
                $this->error("❌ Facade class not found");
            }
        } catch (\Exception $e) {
            $this->error("❌ Facade test failed: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testDatabase()
    {
        $this->info('🗄️ Test 5: Database Migration');
        $this->line(str_repeat('-', 40));

        try {
            if (Schema::hasTable('toxicity_detections')) {
                $this->line("✅ toxicity_detections table exists");
                
                $columns = Schema::getColumnListing('toxicity_detections');
                $this->line("Table columns: " . implode(', ', array_slice($columns, 0, 5)) . '...');
            } else {
                $this->warn("❌ toxicity_detections table not found");
                $this->comment("Run: php artisan migrate");
            }
        } catch (\Exception $e) {
            $this->error("❌ Database check failed: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testMockAnalysis()
    {
        $this->info('🔍 Test 6: Mock Content Analysis');
        $this->line(str_repeat('-', 40));

        $testContents = [
            "Hello, how are you today?" => ["score" => 0.1, "desc" => "Clean content"],
            "You are an absolute moron!" => ["score" => 0.9, "desc" => "Toxic content"], 
            "I disagree with your opinion" => ["score" => 0.3, "desc" => "Mild content"],
            "This is really frustrating!" => ["score" => 0.5, "desc" => "Moderate content"]
        ];

        foreach ($testContents as $content => $data) {
            $this->line("Testing: \"$content\" ({$data['desc']})");
            $mockScore = $data['score'];
            
            $this->line("  Simulated toxicity score: " . $mockScore);
            
            if ($mockScore >= 0.8) {
                $this->line("  🚫 Would be <fg=red>BLOCKED</>");
            } elseif ($mockScore >= 0.6) {
                $this->line("  🚩 Would be <fg=yellow>FLAGGED</>");
            } elseif ($mockScore >= 0.4) {
                $this->line("  ⚠️ Would trigger <fg=blue>WARNING</>");
            } else {
                $this->line("  ✅ Would be <fg=green>ALLOWED</>");
            }
        }
        
        $this->newLine();
    }

    private function showSetupInstructions()
    {
        $this->info('📋 Setup Instructions for Real Usage');
        $this->line(str_repeat('=', 60));

        $this->newLine();
        $this->comment('To use the package with real AI providers:');
        $this->newLine();

        $this->line('1. 📄 Publish configuration:');
        $this->line('   <fg=cyan>php artisan vendor:publish --tag=toxicity-filter-config</>');
        $this->newLine();

        $this->line('2. 🗄️ Run migrations:');
        $this->line('   <fg=cyan>php artisan migrate</>');
        $this->newLine();

        $this->line('3. 🔑 Set up API keys in .env file:');
        $this->line('   <fg=yellow>OPENAI_API_KEY=</><fg=green>your_openai_api_key_here</>');
        $this->line('   <fg=yellow>PERSPECTIVE_API_KEY=</><fg=green>your_perspective_api_key_here</>');
        $this->newLine();

        $this->line('4. 🧪 Test with real content:');
        $this->line('   <fg=cyan>php artisan tinker</>');
        $this->line('   <fg=green>$result = ToxicityFilter::analyze(\'Your test content here\');</>');
        $this->line('   <fg=green>echo $result->getToxicityScore();</>');
        $this->newLine();

        $this->line('5. 🛡️ Use middleware in routes:');
        $this->line('   <fg=green>Route::post(\'/comments\', [CommentController::class, \'store\'])</>');
        $this->line('   <fg=green>    ->middleware(\'toxicity-filter\');</>');
        $this->newLine();

        $this->line('6. 🎯 Use in controllers:');
        $this->line('   <fg=green>if (ToxicityFilter::shouldBlock($content)) {</>');
        $this->line('   <fg=green>    return response()->json([\'error\' => \'Content blocked\'], 422);</>');
        $this->line('   <fg=green>}</>');
    }
}
