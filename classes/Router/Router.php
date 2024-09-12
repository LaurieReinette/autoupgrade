<?php

namespace PrestaShop\Module\AutoUpgrade\Router;

use PrestaShop\Module\AutoUpgrade\Controller\HomePageController;
use PrestaShop\Module\AutoUpgrade\Controller\UpdatePageController;
use PrestaShop\Module\AutoUpgrade\UpgradeContainer;
use Symfony\Component\HttpFoundation\Request;

class Router
{
    /**
     * @var UpgradeContainer
     */
    protected $upgradeContainer;

    public function __construct(UpgradeContainer $upgradeContainer)
    {
        $this->upgradeContainer = $upgradeContainer;
    }

    const ROUTES = [
        'home' => [
            'controller' => HomePageController::class,
            'method' => 'index',
            'params' => [],
        ],
        'update' => [
            'controller' => UpdatePageController::class,
            'method' => 'index',
            'params' => [],
        ]
    ];

    public function handle(Request $request): string
    {
        $route = $request->query->get('route');

        if (empty(self::ROUTES[$route])) {
            $route = self::ROUTES['home'];
        } else {
            $route = self::ROUTES[$route];
        }

        $method = $route['method'];

        return (new $route['controller']($this->upgradeContainer))->$method($request);
    }
}
