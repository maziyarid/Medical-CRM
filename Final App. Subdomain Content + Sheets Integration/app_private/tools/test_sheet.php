<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';try{print_r((new App\SheetClient())->health());}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
