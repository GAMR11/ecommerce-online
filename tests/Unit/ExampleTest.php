<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(false);//modificamos el test a false para que falle de manera intencional y evaluar el tiempo de respuesta luego del ajuste.
    }
}
