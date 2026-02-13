<?php

namespace tests\Unit\Domain\DTOs;

use Domain\DTOs\ApiResponse;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_can_create_instance_and_get_values()
    {
        $response = new ApiResponse(200, ['key' => 'value'], ['X-Test' => '1']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['key' => 'value'], $response->getBody());
        $this->assertEquals(['X-Test' => '1'], $response->getHeaders());
    }

    public function test_is_accepted_returns_true_for_202()
    {
        $response = new ApiResponse(202);
        $this->assertTrue($response->isAccepted());

        $response = new ApiResponse(200);
        $this->assertFalse($response->isAccepted());
    }

    public function test_is_success_returns_true_for_2xx()
    {
        $response = new ApiResponse(200);
        $this->assertTrue($response->isSuccess());

        $response = new ApiResponse(299);
        $this->assertTrue($response->isSuccess());

        $response = new ApiResponse(400);
        $this->assertFalse($response->isSuccess());
    }

    public function test_is_client_error_returns_true_for_4xx()
    {
        $response = new ApiResponse(400);
        $this->assertTrue($response->isClientError());

        $response = new ApiResponse(499);
        $this->assertTrue($response->isClientError());

        $response = new ApiResponse(500);
        $this->assertFalse($response->isClientError());
    }

    public function test_is_server_error_returns_true_for_5xx()
    {
        $response = new ApiResponse(500);
        $this->assertTrue($response->isServerError());

        $response = new ApiResponse(599);
        $this->assertTrue($response->isServerError());

        $response = new ApiResponse(400);
        $this->assertFalse($response->isServerError());
    }

    public function test_to_array_returns_correct_structure()
    {
        $response = new ApiResponse(201, ['id' => 1], ['X-Key' => 'Val']);

        $expected = [
            'status_code' => 201,
            'body' => ['id' => 1],
            'headers' => ['X-Key' => 'Val'],
        ];

        $this->assertEquals($expected, $response->toArray());
    }
}
