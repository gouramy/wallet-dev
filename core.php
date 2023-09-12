<?php

namespace Wallet;

define('Wallet_DIR', ASTRA_CHILD_THEME_DIR . 'inc/wallet/');
define('Wallet_URL', ASTRA_CHILD_THEME_URI . 'inc/wallet/');
define('Wallet_VER', '0.0.1');

require_once Wallet_DIR . 'inc/wallet-functions.php';
require_once Wallet_DIR . 'classes/customers.php';
require_once Wallet_DIR . 'classes/render.php';
require_once Wallet_DIR . 'classes/helper.php';
require_once Wallet_DIR . 'classes/dashboard.php';
require_once Wallet_DIR . 'classes/wallet.php';

class Core{
    /** INSTANCE */
    protected static $instance;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public Dashboard $dashboard;
    public Helper $helper;
    public Render $render;
    public Customers $customers;

    public function __construct()
    {
        $this->customers = new Customers();
        if (has_wallet_access()){
            $this->dashboard = new Dashboard();
            $this->helper = new Helper();
            $this->render = new Render();
        }
    }
}

Core::get_instance();