<?php

namespace Tests\Unit\Application\UseCases;

use Application\UseCases\ListOccurrencesUseCase;
use Domain\Repositories\OccurrenceRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ListOccurrencesUseCaseTest extends TestCase
{
    public function test_executes_list_on_repository()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $filters = ['status' => 'in_progress'];
        $expectedResult = ['occ1', 'occ2'];

        $repository->expects($this->once())
            ->method('list')
            ->with($filters)
            ->willReturn($expectedResult);

        $useCase = new ListOccurrencesUseCase($repository);
        $result = $useCase->execute($filters);

        $this->assertEquals($expectedResult, $result);
    }
}
