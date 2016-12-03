<?php

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function assertWebhookDataIsValid($expected, $actual, $branch, $source)
    {
        $this->assertInternalType('array', $actual);

        $this->assertArrayHasKey('reason', $actual);
        $this->assertArrayHasKey('branch', $actual);
        $this->assertArrayHasKey('source', $actual);
        $this->assertArrayHasKey('build_url', $actual);
        $this->assertArrayHasKey('commit', $actual);
        $this->assertArrayHasKey('committer', $actual);
        $this->assertArrayHasKey('committer_email', $actual);

        $this->assertEquals($expected['message'], $actual['reason']);
        $this->assertEquals($branch, $actual['branch']);
        $this->assertEquals($source, $actual['source']);
        $this->assertEquals($expected['url'], $actual['build_url']);
        $this->assertEquals($expected['id'], $actual['commit']);
        $this->assertEquals($expected['committer']['name'], $actual['committer']);
        $this->assertEquals($expected['committer']['email'], $actual['committer_email']);
    }
}
