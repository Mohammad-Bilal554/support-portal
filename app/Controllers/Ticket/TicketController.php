<?php
declare(strict_types=1);
namespace App\Controllers\Ticket;
use App\Core\Controller;
use App\Core\Request;
class TicketController extends Controller
{
    public function __call(string $name, array $args): string
    {
        return '<h2 style="font-family:monospace;padding:2rem;color:#3b82f6;">'
             . '🚧 TicketController::${name}() — Module 6+ (Tickets) not yet built.</h2>';
    }
}
