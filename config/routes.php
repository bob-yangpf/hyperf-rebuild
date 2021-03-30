<?php
return [
    ['GET', '/hello/index', [\App\Controller\HelloController::class, 'index']],
    ['GET', '/hello/hello', [\App\Controller\HelloController::class, 'hello']],

];