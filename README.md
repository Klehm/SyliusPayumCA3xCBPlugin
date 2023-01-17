<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Sylius 3xCB Crédit Agricole Plugin</h1>

<p align="center">3xCB Crédit Agricole Gateway</p>


## Quickstart Installation

1. Install plugin
```
   composer require klehm/sylius-payum-ca3xcb-plugin
```

2. Make sure to add bundle on bundles.php (if autorecipe is not used)

    ```php
    Klehm\SyliusPayumCA3xcbPlugin\KlehmSyliusPayumCA3xcbPlugin::class => ['all' => true],
    ```




# Env variable for local capture if IPN cannot be call

```
KLEHM_SYLIUS_PAYUM_CA3XCB_PLUGIN_LOCAL_CAPTURE=1
```
