<?php

namespace Tests\Unit\Support;

use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\IntegrationLog;
use App\Support\IntegrationOutcome;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * `outcome` on an integration log is either "success" or an exception's kind().
 *
 * Those two lists live in different places, so they can drift. The symptom
 * would be a log viewer whose filter silently never matches a whole class of
 * failure -- which is exactly when someone is trying to diagnose one.
 */
class IntegrationOutcomeTest extends TestCase
{
    /**
     * @return list<class-string<EsbException>>
     */
    private function exceptionClasses(): array
    {
        $classes = [];

        foreach (Finder::create()->files()->in(app_path('Integrations/ESB/Exceptions'))->name('*.php') as $file) {
            /** @var SplFileInfo $file */
            $class = 'App\\Integrations\\ESB\\Exceptions\\'.$file->getBasename('.php');

            if (! class_exists($class) || ! is_subclass_of($class, EsbException::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    #[Test]
    public function every_exception_kind_is_a_known_outcome(): void
    {
        $known = IntegrationOutcome::all();

        foreach ($this->exceptionClasses() as $class) {
            $kind = (new ReflectionClass($class))->newInstanceWithoutConstructor()->kind();

            $this->assertContains(
                $kind,
                $known,
                "{$class}::kind() returns \"{$kind}\", which IntegrationOutcome does not list.",
            );
        }
    }

    #[Test]
    public function success_is_a_known_outcome(): void
    {
        $this->assertContains(IntegrationLog::OUTCOME_SUCCESS, IntegrationOutcome::all());
    }

    #[Test]
    public function it_lists_no_outcome_that_nothing_can_produce(): void
    {
        $producible = [IntegrationLog::OUTCOME_SUCCESS];

        foreach ($this->exceptionClasses() as $class) {
            $producible[] = (new ReflectionClass($class))->newInstanceWithoutConstructor()->kind();
        }

        foreach (IntegrationOutcome::all() as $outcome) {
            $this->assertContains(
                $outcome,
                $producible,
                "IntegrationOutcome lists \"{$outcome}\", but nothing produces it.",
            );
        }
    }

    #[Test]
    public function failures_excludes_success(): void
    {
        $this->assertNotContains(IntegrationLog::OUTCOME_SUCCESS, IntegrationOutcome::failures());
        $this->assertContains('timeout', IntegrationOutcome::failures());
    }
}
