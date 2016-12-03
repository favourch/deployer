<?php

use Illuminate\Http\Request;
use REBELinBLUE\Deployer\Http\Webhooks\Github;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;

class GithubTest extends TestCase
{
    private function mockRequestIsFromGithub($isValid)
    {
        $request = $this->getMockBuilder(Request::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $header = $this->getMockBuilder(HeaderBag::class)
                       ->disableOriginalConstructor()
                       ->getMock();

        $header->expects($this->once())
               ->method('has')
               ->with($this->equalTo('X-GitHub-Event'))
               ->willReturn($isValid);

        $request->headers = $header;

        return $request;
    }

    private function mockEventRequest($event)
    {
        $request = $this->getMockBuilder(Request::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $request->expects($this->once())
                ->method('header')
                ->with($this->equalTo('X-GitHub-Event'))
                ->willReturn($event);

        return $request;
    }

    private function mockPullRequest()
    {
        $request = $this->mockEventRequest('push');

        $payload = $this->getMockBuilder(ParameterBag::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $request->expects($this->once())
                ->method('json')
                ->willReturn($payload);

        $payload->expects($this->once())
                ->method('has')
                ->with($this->equalTo('after'))
                ->willReturn(true);

        $payload->expects($this->once())
                ->method('get')
                ->with($this->equalTo('after'))
                ->willReturn('0000000000000000000000000000000000000000');

        return $request;
    }

    private function mockRequestWithPayload(array $data, $ref)
    {
        $request = $this->mockEventRequest('push');

        $payload = $this->getMockBuilder(ParameterBag::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $request->expects($this->once())
                ->method('json')
                ->willReturn($payload);

        $payload->expects($this->once())
                ->method('has')
                ->with($this->equalTo('after'))
                ->willReturn(false);

        $payload->expects($this->at(1))
                ->method('get')
                ->with($this->equalTo('head_commit'))
                ->willReturn($data);

        $payload->expects($this->at(2))
                ->method('get')
                ->with($this->equalTo('ref'))
                ->willReturn('refs/' . $ref);

        return $request;
    }

    public function testIsRequestOriginValid()
    {
        $request = $this->mockRequestIsFromGithub(true);

        $github = new Github($request);
        $this->assertTrue($github->isRequestOrigin());
    }

    public function testIsRequestOriginInvalid()
    {
        $request = $this->mockRequestIsFromGithub(false);

        $github = new Github($request);
        $this->assertFalse($github->isRequestOrigin());
    }

    public function testHandleClosedPullRequest()
    {
        $request = $this->mockPullRequest();

        $github = new Github($request);
        $this->assertFalse($github->handlePush());
    }

    /**
     * @param string $branch
     * @param string $ref
     * @dataProvider getBranch
     */
    public function testHandlePushEventValid($branch, $ref)
    {
        $expected = [
            'message'   => 'Commit log',
            'url'       => 'http://www.example.com/',
            'id'        => 'abc123456xyz',
            'committer' => [
                'name'  => 'John Smith',
                'email' => 'john.smith@example.com'
            ]
        ];

        $request = $this->mockRequestWithPayload($expected, $ref);

        $github = new Github($request);
        $actual = $github->handlePush();

        $this->assertWebhookDataIsValid($expected, $actual, $branch, 'Github');
    }

    public function getBranch() {
        return [
            'Branch' => ['master', 'heads/master'],
            'Tag'    => ['1.0.0', 'tags/1.0.0']
        ];
    }

    /**
     * @param string $event
     * @dataProvider getUnsupportedEvents
     */
    public function testHandleUnsupportedEvent($event)
    {
        $request = $this->mockEventRequest($event);

        $github = new Github($request);
        $this->assertFalse($github->handlePush());
    }

    public function getUnsupportedEvents()
    {
        return array_chunk([
            '*', 'commit_comment', 'create', 'delete', 'deployment', 'deployment_status', 'fork', 'gollum',
            'issue_comment', 'issues', 'label', 'member', 'membership', 'milestone', 'organization', 'page_build',
            'ping', 'public', 'pull_request_review_comment', 'pull_request_review', 'pull_request', 'repository',
            'release', 'status', 'team', 'team_add', 'watch', 'not_a_github_event'
        ], 1);
    }
}
