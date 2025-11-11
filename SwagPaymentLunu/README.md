# Lunu Cryptocurrency Payment Plugin for Shopware 5

Accept cryptocurrency payments easily and securely with Lunu Widget. Support for Bitcoin, Ethereum, and other major cryptocurrencies.

## Features

- 🔒 **Secure Payment Processing** - Uses HMAC-SHA256 for secure token generation
- 💰 **Multi-Currency Support** - Accept Bitcoin, Ethereum, and other major cryptocurrencies
- 🧪 **Sandbox Mode** - Test payments without real transactions
- 📝 **Comprehensive Logging** - Detailed error logging for troubleshooting
- ⚡ **Easy Integration** - Simple configuration and setup process
- 🌍 **Multi-Language** - Support for English and German

## Requirements

- Shopware 5.2.13 or higher
- PHP 7.0 or higher
- cURL extension
- JSON extension
- Active Lunu account ([Sign up here](https://lunu.io))

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

1. Go to **Configuration** → **Plugin Manager**
2. Find "Lunu Cryptocurrency Payment" and click the pencil icon
3. Configure the following settings:

### Required Settings

- **App ID**: Your Lunu App ID (get it from your Lunu dashboard)
- **API Secret**: Your Lunu API Secret (keep this confidential!)

### Optional Settings

- **Enable Sandbox Mode**: Enable this to test payments without real transactions

### Getting Your API Credentials

1. Log in to your [Lunu Dashboard](https://lunu.io)
2. Navigate to **Settings** → **API Keys**
3. Copy your App ID and API Secret
4. Paste them into the plugin configuration

## Usage

### For Store Admins

1. After configuration, the payment method will appear in:
   - **Configuration** → **Payment Methods**
2. Make sure the payment method is active
3. Assign it to the desired customer groups and countries

### For Customers

1. During checkout, select "Lunu Widget" as the payment method
2. Click "Confirm Order"
3. You'll be redirected to the Lunu payment widget
4. Select your preferred cryptocurrency
5. Complete the payment
6. You'll be redirected back to the shop

## Payment Flow

```
Customer Checkout
    ↓
Select Lunu Payment
    ↓
Redirect to Lunu Widget
    ↓
Select Cryptocurrency
    ↓
Complete Payment
    ↓
Return to Shop
    ↓
Order Completed
```

## Security

- **HMAC-SHA256**: All payment tokens are generated using secure HMAC-SHA256 hashing
- **Token Validation**: Every payment return is validated with secure token verification
- **HTTPS Only**: All API communications use HTTPS
- **No Sensitive Data Storage**: No credit card or cryptocurrency wallet information is stored

## Troubleshooting

### Payment fails with "Missing API credentials"

**Solution**: Make sure you've configured both App ID and API Secret in the plugin settings.

### Payment widget doesn't load

**Solution**: 
- Check your internet connection
- Verify your API credentials are correct
- Check the Shopware logs for detailed error messages

### Order stays in "Payment pending" status

**Solution**:
- Check if the payment was completed in your Lunu dashboard
- Review the Shopware logs for any errors
- Contact Lunu support if the issue persists

### Where to find logs?

Logs are available in:
- Shopware backend: **System** → **Log**
- Look for entries with "Lunu" in the message

## API Endpoints

### Production
- API: `https://api.lunupay.com/api/v1/`
- Widget: `https://widget.lunupay.com/`

### Sandbox
- API: `https://api.sandbox.lunupay.com/api/v1/`
- Widget: `https://widget.sandbox.lunupay.com/`

## Support

- **Email**: support@lunu.io
- **Website**: [https://lunu.io](https://lunu.io)
- **Documentation**: [https://docs.lunupay.com](https://docs.lunupay.com)

## Changelog

### Version 1.0.0 (2025-10-10)

- Initial release
- Support for cryptocurrency payments via Lunu Widget
- Secure payment processing with HMAC-SHA256
- Sandbox mode
- Comprehensive error handling and logging
- Multi-language support (EN/DE)

## License

This plugin is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Credits

Developed by [Lunu Solutions GmbH](https://lunu.io)

---

**Note**: This plugin requires an active Lunu account. Sign up at [https://lunu.io](https://lunu.io) to get started.


