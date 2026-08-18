<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';try{echo "Credit response: ".(new App\TsmsClient())->credit().PHP_EOL;}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
