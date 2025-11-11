# Lunu Widget Gateway Payment Plugin for Shopware 5

Gateway payment integration for Lunu cryptocurrency payment service. Provides secure cryptocurrency payment processing using a gateway payment flow.

## Features

- 🔒 **Secure Gateway Integration** - Token-based payment verification
- 💳 **Gateway Payment Flow** - Customers are forwarded to payment provider
- 🔐 **HMAC-SHA256 Security** - Secure token generation and validation
- 📝 **Error Logging** - Comprehensive error handling and logging
- 🌍 **Multi-Language** - Support for English and German

## Requirements

- Shopware 5.2.13 or higher
- PHP 7.0 or higher
- Active Lunu account

## Installation

### Via ZIP Upload

1. Download the plugin ZIP file
2. In your Shopware backend, go to **Configuration** → **Plugin Manager**
3. Click **Upload plugin** and select the ZIP file
4. Install and activate the plugin

### Manual Installation

1. Copy the plugin folder to `custom/plugins/`
2. Clear the Shopware cache
3. Install and activate the plugin via the Plugin Manager

## Configuration

This plugin uses gateway payment mode where customers are redirected to an external payment provider.

1. After installation, activate the payment method
2. Go to **Configuration** → **Payment Methods**
3. Configure the "Lunu Widget (Gateway)" payment method
4. Assign it to desired customer groups and countries

## Payment Flow

```
Customer Checkout
    ↓
Select Gateway Payment
    ↓
Forward to Payment Provider
    ↓
Complete Payment
    ↓
Return to Shop
    ↓
Verify Payment Token
    ↓
Order Completed
```

## Technical Details

### Payment Token Generation

The plugin uses secure HMAC-SHA256 hashing to generate payment tokens:

```php
hash_hmac('sha256', $data, $secret);
```

### Token Validation

All payment returns are validated using `hash_equals()` to prevent timing attacks:

```php
hash_equals($expectedToken, $receivedToken);
```

## Gateway URL Configuration

The gateway URL is configured in the controller. To change the payment provider URL, modify:

```php
protected function getProviderUrl()
{
    return 'https://your-payment-provider.com/pay';
}
```

## Troubleshooting

### Payment validation fails

**Solution**: 
- Verify the payment provider URL is correct
- Check that tokens are generated consistently
- Review Shopware logs for detailed errors

### Gateway redirect fails

**Solution**:
- Ensure SSL/HTTPS is properly configured
- Check that all required URL parameters are passed
- Verify the payment provider is accessible

### Where to find logs?

Logs are available in:
- Shopware backend: **System** → **Log**
- Look for entries with "Lunu Widget" in the message

## Security Features

- **Token-Based Verification**: All payments are verified with secure tokens
- **HMAC-SHA256**: Industry-standard hashing algorithm
- **Timing Attack Prevention**: Uses `hash_equals()` for constant-time comparison
- **Error Handling**: Comprehensive try-catch blocks prevent information leakage

## Customization

### Modifying Payment Description

Edit the `additionalDescription` in `LunuWidget.php`:

```php
'additionalDescription' =>
    '<img src="https://your-logo-url.com/logo.png"/>'
    . '<div id="payment_desc">'
    . '  Your custom payment description'
    . '</div>'
```

### Adding Custom Parameters

Modify the `getUrlParameters()` method in the Payment controller to add custom parameters to the gateway URL.

## Support

- **Email**: support@lunu.io
- **Website**: [https://lunu.io](https://lunu.io)
- **Documentation**: [https://docs.lunupay.com](https://docs.lunupay.com)

## Changelog

### Version 1.0.0 (2025-10-10)

- Initial release
- Gateway payment method support
- Secure token-based payment verification
- Improved error handling
- Production-ready code

## License

This plugin is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Developer Notes

### File Structure

```
!LunuWidget/
├── Components/
│   └── Payment/
│       ├── PaymentResponse.php    # Payment response model
│       └── PaymentService.php     # Payment service logic
├── Controllers/
│   └── Frontend/
│       └── Payment.php            # Payment controller
├── Resources/
│   └── services.xml               # Service container configuration
├── LunuWidget.php                 # Main plugin class
├── plugin.xml                     # Plugin metadata
├── composer.json                  # Composer configuration
└── README.md                      # This file
```

### Extending the Plugin

To extend this plugin:

1. Create a new plugin that depends on LunuWidget
2. Override the payment controller
3. Extend the PaymentService class for custom logic

## Credits

Developed by [Lunu Solutions GmbH](https://lunu.io)

---

**Note**: This is a gateway payment plugin. Configure your payment provider URL accordingly.


