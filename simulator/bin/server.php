<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use Simulator\Application\GenerateServicesHandler;
use Simulator\Application\SimulationState;
use Simulator\Application\StartSimulationHandler;
use Simulator\Application\StopSimulationHandler;
use Simulator\Application\TickHandler;
use Simulator\Domain\Service\GpsNoise;
use Simulator\Domain\Service\PolylineCodec;
use Simulator\Domain\Service\ServiceFactory;
use Simulator\Infrastructure\Http\Router;
use Simulator\Infrastructure\Persistence\PdoServiceRepository;
use Simulator\Infrastructure\Persistence\PdoTrackingRepository;

// --- Configuration from environment ---
$dbHost = getenv('DB_HOST')     ?: 'mysql';
$dbPort = getenv('DB_PORT')     ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'livetracking';
$dbUser = getenv('DB_USERNAME') ?: 'livetracking';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';
$port   = getenv('SIMULATOR_PORT') ?: '8001';

// --- PDO connection ---
$pdo = new PDO(
    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
);

// --- Domain services ---
$codec   = new PolylineCodec();
$noise   = new GpsNoise();
$factory = new ServiceFactory($codec);

// --- Repositories ---
$serviceRepo  = new PdoServiceRepository($pdo, $codec);
$trackingRepo = new PdoTrackingRepository($pdo);

// --- Application ---
$state         = new SimulationState();
$generateH     = new GenerateServicesHandler($factory, $serviceRepo, $trackingRepo);
$startH        = new StartSimulationHandler($serviceRepo, $state);
$stopH         = new StopSimulationHandler($state);
$tickH         = new TickHandler($state, $trackingRepo, $noise);

// --- Infrastructure ---
$loop = Loop::get();

// A single periodic timer runs for the whole process lifetime. TickHandler
// guards on SimulationState::isRunning(), so ticks are no-ops until a run
// is started — no need to couple the timer to state transitions.
$loop->addPeriodicTimer(5.0, fn() => $tickH->tick());

$router = new Router($generateH, $startH, $stopH, $state);

$http   = new HttpServer($loop, $router);
$socket = new SocketServer("0.0.0.0:{$port}", [], $loop);
$http->listen($socket);

echo "Simulator listening on http://0.0.0.0:{$port}" . PHP_EOL;

$loop->run();
