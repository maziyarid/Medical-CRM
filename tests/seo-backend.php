<?php
declare(strict_types=1);
// Standalone synthetic unit checks. No app bootstrap, .env, DB or live requests.
$root = dirname(__DIR__) . '/dashboard.drbastaninejad.com';
require $root . '/app/Core/Controller.php';
require $root . '/app/Core/Request.php';
require $root . '/app/Services/SeoReportingService.php';
require $root . '/app/Controllers/SeoReportingController.php';
require $root . '/app/Middleware/SeoNoStoreMiddleware.php';
use App\Core\Request;
use App\Services\SeoReportingService;
use App\Controllers\SeoReportingController;
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($label); }
    $checks++;
}
function requestFor(?array $user, array $query = []): Request {
    $r = new Request(); $r->user = $user; $r->query = $query;
    $r->headers = []; $r->body = []; $r->params = []; $r->method = 'GET';
    return $r;
}
$admin = ['id'=>10,'clinic_id'=>7,'user_type'=>'staff','role'=>'admin','roles'=>['admin']];
$controller = new SeoReportingController();
check($controller->summary(requestFor(null))['status'] === 401, 'Anonymous request denied');
foreach (['patient','doctor','receptionist','nurse','staff'] as $role) {
    $user = array_merge($admin, ['role'=>$role,'roles'=>[$role],'user_type'=>$role==='patient'?'patient':'staff']);
    check($controller->summary(requestFor($user))['status'] === 403, 'Role denied: '.$role);
}
foreach ([0,-1,null,'not-an-id'] as $clinic) {
    check($controller->summary(requestFor(array_merge($admin,['clinic_id'=>$clinic])))['status'] === 403, 'Invalid clinic denied');
}
check($controller->summary(requestFor(array_merge($admin,['user_type'=>'patient','roles'=>['super_admin']])))['status'] === 403, 'Patient admin role cannot bypass');
foreach ([0,-1,null,'invalid'] as $id) {
    check($controller->summary(requestFor(array_merge($admin,['id'=>$id])))['status']===403,'Invalid staff identity denied');
}
foreach ([[],[['admin']], 'admin', [null], ['Admin']] as $roles) {
    check($controller->summary(requestFor(array_merge($admin,['roles'=>$roles])))['status']===403,'Malformed or missing role denied');
}
foreach (['admin','super_admin'] as $role) {
    $result=$controller->summary(requestFor(array_merge($admin,['role'=>$role,'roles'=>[$role]])));
    check($result['status']===503 && $result['ok']===false, 'Disabled endpoint never reports live success');
    check($result['data']===null && $result['meta']['code']==='reporting_unconfigured', 'No invented metrics');
}
foreach ([['projectId'=>'other'],['site'=>'other.invalid'],['clinic_id'=>8],['period'=>'all'],['comparison'=>'year'],['endDate'=>'2026-02-30'],['endDate'=>'2099-01-01'],['period'=>['last_7d']]] as $query) {
    check($controller->summary(requestFor($admin,$query))['status']===422, 'Invalid query rejected');
}
check($controller->summary(requestFor($admin,['period'=>'last_7d','comparison'=>'none','endDate'=>'2026-01-31']))['status']===503, 'Valid historical request stays disabled');
foreach ([['endDate'=>'0000-01-01'],['period'=>'last_7d','comparison'=>'previous','endDate'=>'0001-01-07'],['period'=>'last_7d','comparison'=>'none','endDate'=>'0001-01-06']] as $query) {
    check($controller->summary(requestFor($admin,$query))['status']===422,'Derived date underflow denied');
}
check($controller->summary(requestFor($admin,['period'=>'last_7d','comparison'=>'none','endDate'=>'0001-01-07']))['status']===503,'Earliest noncomparative date accepted');
check($controller->summary(requestFor($admin,['period'=>'last_7d','comparison'=>'previous','endDate'=>'0001-01-14']))['status']===503,'Earliest comparative date accepted');
$_ENV['MS_ROBOT_ENABLED']='1';
check($controller->summary(requestFor($admin))['status']===503,'Environment toggle cannot activate');
check($controller->summary(requestFor(array_merge($admin,['clinic_id'=>99])))['data']===null,'Unknown valid clinic receives no data');
$router = new class {
    public array $routes=[];
    public function get(string $path, array $handler, array $middleware):void { $this->routes[$path]=[$handler,$middleware]; }
};
require $root . '/config/routes.analytics.php';
check(count($router->routes)===2,'Clinic reports route preserved');
check($router->routes['/api/v1/analytics/summary'][0][0] === 'App\\Controllers\\AnalyticsController','Clinic controller preserved');
$middleware=$router->routes['/api/v1/analytics/seo'][1];
check($middleware[0]==='App\\Middleware\\SeoNoStoreMiddleware','No-store before authentication');
check($middleware[1]==='App\\Middleware\\AuthMiddleware','Existing auth cannot be bypassed');
check(is_callable($middleware[2]),'Existing permission middleware retained');
check((new App\Middleware\SeoNoStoreMiddleware())->handle(requestFor($admin))===null,'No-store middleware does not authenticate');
$serialized=json_encode($controller->summary(requestFor($admin)),JSON_THROW_ON_ERROR);
foreach (['credential','token','clinic_id','projectId','metricValue','patient'] as $forbidden) {
    check(!str_contains($serialized,$forbidden), 'No private internals: '.$forbidden);
}
echo "PASS: $checks synthetic PHP checks\n";
