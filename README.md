# sylius-trustpay-plugin

Plugin for card payment gateway on sylius

<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>
<h1 align="center">Trustpay Plugin</h1>

<p align="center">Sylius Plugin for integrate Trustpay card form payment.</p>

## Quickstart Installation

- Install with `composer`:

  `composer require ayminovitch/sylius-trustpay-plugin "dev-main"`

- Create `template`:

  You should copy all directory or files in `src/Resources/view` and paste into your `templates` directory of your app.

## Usage

This plugin add a new payment method for CardPayment via Trustpay Gateway.
The form is embed in your store , no redirection.

### Test : Opening Sylius with your plugin

- Using `test` environment:

  ```bash
  (cd tests/Application && APP_ENV=test bin/console sylius:fixtures:load)
  (cd tests/Application && APP_ENV=test bin/console server:run -d public)
  ```

- Using `dev` environment:

  ```bash
  (cd tests/Application && APP_ENV=dev bin/console sylius:fixtures:load)
  (cd tests/Application && APP_ENV=dev bin/console server:run -d public)
  ```
