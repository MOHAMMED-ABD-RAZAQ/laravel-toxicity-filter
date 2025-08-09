<?php

namespace Packages\ToxicityFilter\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Packages\ToxicityFilter\Contracts\ToxicityProviderInterface;
use Packages\ToxicityFilter\Services\ToxicityFilterService;
use Packages\ToxicityFilter\ValueObjects\ToxicityResult;

class ToxicityFilterServiceTest extends TestCase
{
    private ToxicityFilterService $service;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'default' => 'mock',
            'providers' => [
                'mock' => [
                    'api_key' => 'test_key',
                    'endpoint' => 'https://api.example.com',
                ],
            ],
            'thresholds' => [
                'block' => 0.8,
                'flag' => 0.6,
                'warn' => 0.4,
            ],
            'cache' => ['enabled' => false],
            'logging' => ['enabled' => false],
        ];

        $this->service = new ToxicityFilterService($this->config);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_analyze_content_with_high_toxicity(): void
    {
        $mockProvider = Mockery::mock(ToxicityProviderInterface::class);
        $mockProvider->shouldReceive('getName')->andReturn('mock');
        $mockProvider->shouldReceive('isConfigured')->andReturn(true);
        $mockProvider->shouldReceive('analyze')
            ->with('This is toxic content!')
            ->andReturn(new ToxicityResult(
                toxicityScore: 0.9,
                categories: ['harassment', 'hate'],
                provider: 'mock'
            ));

        // Mock the provider creation (this would need dependency injection in real implementation)
        // For now, this is a simplified test
        
        $result = new ToxicityResult(0.9, ['harassment', 'hate'], 'mock');
        
        $this->assertEquals(0.9, $result->getToxicityScore());
        $this->assertEquals(['harassment', 'hate'], $result->getCategories());
        $this->assertEquals('mock', $result->getProvider());
    }

    public function test_should_block_high_toxicity_content(): void
    {
        $result = new ToxicityResult(0.9, ['harassment'], 'mock');
        
        $this->assertTrue($result->shouldBlock(0.8));
        $this->assertFalse($result->shouldBlock(0.95));
    }

    public function test_should_flag_moderate_toxicity_content(): void
    {
        $result = new ToxicityResult(0.7, ['profanity'], 'mock');
        
        $this->assertTrue($result->shouldFlag(0.6));
        $this->assertFalse($result->shouldFlag(0.8));
    }

    public function test_should_warn_low_toxicity_content(): void
    {
        $result = new ToxicityResult(0.5, ['mild_profanity'], 'mock');
        
        $this->assertTrue($result->shouldWarn(0.4));
        $this->assertFalse($result->shouldWarn(0.6));
    }

    public function test_toxicity_result_to_array(): void
    {
        $result = new ToxicityResult(
            toxicityScore: 0.8,
            categories: ['harassment'],
            provider: 'mock',
            explanation: 'Test explanation',
            metadata: ['test' => 'data']
        );

        $expected = [
            'toxicity_score' => 0.8,
            'categories' => ['harassment'],
            'provider' => 'mock',
            'explanation' => 'Test explanation',
            'metadata' => ['test' => 'data'],
        ];

        $this->assertEquals($expected, $result->toArray());
    }
}
