# Mautic Twig Enhancements

A Mautic 6 plugin that enables full Twig templating in emails, allowing conditionals, loops, and filters with API token data.

## Why This Plugin?

Mautic's default token system (`{contactfield=firstname}`) is limited to simple value replacement. If you need conditional logic, loops, or dynamic content based on API data, you're stuck.

**This plugin solves that by:**
- Processing Twig syntax (`{{ }}`, `{% %}`) directly in your emails
- Working with tokens passed via the API
- Automatically handling GrapeJS editor quirks
- Failing gracefully - emails still send even if Twig has errors

## Features

- **Conditionals** - Show/hide content based on values
- **Loops** - Iterate over arrays (order items, product lists, etc.)
- **Filters** - Format dates, numbers, text transformations
- **Automatic processing** - No special markers required
- **GrapeJS compatible** - Auto-fixes HTML entity encoding (`&gt;` to `>`)
- **Safe** - Errors are logged, but emails still deliver
- **Processes everything** - Subject line, HTML body, and plain text

## Requirements

- Mautic 6.0+
- PHP 8.1+

## Installation

1. Download or clone this repository

2. Rename the folder to `MauticTwigEnhancementsBundle`

3. Copy to your Mautic `plugins/` directory:
   ```
   plugins/MauticTwigEnhancementsBundle/
   ```

4. Clear Mautic cache:
   ```bash
   php bin/console cache:clear
   ```

5. Go to **Settings > Plugins** and click **Install/Upgrade Plugins**

6. The plugin should appear as "Twig Enhancements"

### Verify Installation

After installation, the plugin processes emails automatically. No configuration needed.

## Usage

### Token Formats

You can use both Mautic tokens and Twig syntax:

| Format | Type | Example |
|--------|------|---------|
| `{token_name}` | Mautic token | `{order_id}` |
| `{{ token_name }}` | Twig variable | `{{ order_id }}` |
| `{{ tokens.token_name }}` | Via tokens object | `{{ tokens.order_id }}` |
| `{{ lead.fieldname }}` | Contact field | `{{ lead.firstname }}` |

### Basic Conditionals

```twig
{% if orderTotal > 100 %}
  You qualify for free shipping!
{% else %}
  Add ${{ 100 - orderTotal }} more for free shipping.
{% endif %}
```

### Check if Variable Exists

For optional fields, always check if defined:

```twig
{% if discount is defined and discount %}
  You saved {{ discount }}!
{% endif %}
```

Or use the `default` filter:

```twig
Hello {{ lead.firstname|default('there') }}!
```

### Loops

Perfect for order confirmations, product lists, etc.:

```twig
{% for item in items %}
  - {{ item.name }}: ${{ item.price }}
{% endfor %}
```

With index:

```twig
{% for item in items %}
  {{ loop.index }}. {{ item.name }}
{% endfor %}
```

### Twig Filters

All standard Twig filters work:

```twig
{{ lead.firstname|upper }}              {# JOHN #}
{{ lead.firstname|lower }}              {# john #}
{{ lead.firstname|capitalize }}         {# John #}
{{ lead.firstname|title }}              {# John Doe #}

{{ orderDate|date('F j, Y') }}          {# January 15, 2024 #}
{{ orderDate|date('m/d/Y') }}           {# 01/15/2024 #}

{{ price|number_format(2, '.', ',') }}  {# 1,234.56 #}
{{ description|slice(0, 100) }}...      {# First 100 chars #}
{{ items|length }}                      {# Count of items #}
{{ items|first }}                       {# First item #}
{{ items|last }}                        {# Last item #}
```

### Lead/Contact Data

Access any contact field:

```twig
Hello {{ lead.firstname }}!

{% if lead.country == 'Jamaica' %}
  Free shipping to Jamaica!
{% endif %}

Your email: {{ lead.email }}
Your points: {{ lead.points|default(0) }}
```

`contact` is an alias for `lead`:

```twig
{{ contact.firstname }}  {# Same as lead.firstname #}
```

## API Usage

When sending emails via Mautic API, pass tokens in your payload:

```json
{
  "email": 1,
  "contact": 123,
  "tokens": {
    "order_id": "ORD-12345",
    "date_time": "2024-01-15 10:30 AM",
    "phone_number": "+1 876 555 1234",
    "operator": "Digicel",
    "country": "Jamaica",
    "received_amount": "J$500.00",
    "cost": "$5.00",
    "processing_fee": "$0.50",
    "total": "$5.50",
    "discount": "$1.00",
    "credit_used": "$2.00",
    "receipt_text": "Thank you for your purchase!",
    "items": [
      {"name": "Airtime", "price": 5.00},
      {"name": "Data Bundle", "price": 10.00}
    ]
  }
}
```

**Note:** Token keys work with or without curly braces (`order_id` or `{order_id}`).

## GrapeJS / MJML Usage

When using the GrapeJS email builder (Mautic's default), you need to wrap Twig **control structures** in `<mj-raw>` tags to preserve them during MJML compilation.

### What needs `<mj-raw>` wrapping

| Syntax | Needs `<mj-raw>`? | Example |
|--------|-------------------|---------|
| `{% if %}` | Yes | `<mj-raw>{% if x %}</mj-raw>` |
| `{% endif %}` | Yes | `<mj-raw>{% endif %}</mj-raw>` |
| `{% for %}` | Yes | `<mj-raw>{% for item in items %}</mj-raw>` |
| `{% endfor %}` | Yes | `<mj-raw>{% endfor %}</mj-raw>` |
| `{{ variable }}` | No | Works inside `<mj-text>` directly |

### MJML Template Example

```html
<!-- Conditional section - wrap control tags in mj-raw -->
<mj-raw>{% if discount is defined and discount %}</mj-raw>
<mj-section>
  <mj-column>
    <mj-text>You saved {{ discount }}!</mj-text>
  </mj-column>
</mj-section>
<mj-raw>{% endif %}</mj-raw>
```

### What the plugin fixes automatically

GrapeJS converts `<` and `>` to `&lt;` and `&gt;`. The plugin automatically converts these back inside Twig tags, so comparisons work:

```html
<!-- This works - plugin auto-fixes the > -->
<mj-raw>{% if count > 10 %}</mj-raw>
```

**You don't need:**
- `{% TWIG_BLOCK %}` markers (unlike Advanced Templates Bundle)
- HTML comment workarounds (`<!-- {% if x > 1 %} -->`)

## Migrating from Advanced Templates Bundle

If you're coming from [mautic-advanced-templates-bundle](https://github.com/Logicify/mautic-advanced-templates-bundle):

| Advanced Templates | This Plugin |
|--------------------|-------------|
| `{% TWIG_BLOCK %}...{% END_TWIG_BLOCK %}` | Not needed - just write Twig |
| `<mj-raw>{% if ... %}</mj-raw>` | `{% if ... %}` |
| Comment workarounds for `>` `<` | Auto-fixed |
| Requires markers for processing | Processes all Twig automatically |

### Migration Steps

1. Remove all `{% TWIG_BLOCK %}` and `{% END_TWIG_BLOCK %}` markers
2. Remove `<mj-raw>` wrappers around Twig code
3. Remove HTML comment workarounds (`<!-- {% if x > 1 %} -->`)
4. Keep your actual Twig logic as-is

## Error Handling

- **Syntax errors**: Original content is preserved, email still sends
- **Missing variables**: Use `|default()` filter or `is defined` check
- **All errors logged**: Check `var/logs/mautic.log` for debugging

Example log entry:
```
[ERROR] TwigEnhancements: Template processing failed {"error":"...","file":"...","line":...}
```

## Troubleshooting

### Plugin not appearing
- Ensure folder is named exactly `MauticTwigEnhancementsBundle`
- Clear cache: `php bin/console cache:clear`
- Check file permissions (readable by web server)
- **If cloned as root**: The plugin directory may be owned by root, preventing Mautic from loading it. Fix with:
  ```bash
  chown -R www-data:www-data plugins/MauticTwigEnhancementsBundle
  ```
  (Replace `www-data` with your web server user if different)

### Verify plugin is active via console

If the plugin doesn't appear in the UI but you want to confirm it's loaded:

```bash
# Check if services are registered
php bin/console debug:container | grep -i twig

# Check event listeners
php bin/console debug:event-dispatcher EmailEvents::EMAIL_ON_SEND

# Check if bundle is loaded
php bin/console debug:container --parameter=kernel.bundles | grep Twig
```

### Twig not processing
- Verify plugin is installed: **Settings > Plugins**
- Check Mautic logs: `var/logs/mautic.log`
- Validate Twig syntax at [twigfiddle.com](https://twigfiddle.com/)

### Variables not available
- Token names are **case-sensitive**
- API tokens need curly braces: `"{order_id}": "value"`
- Use `{{ variable|default('fallback') }}` for optional values
- Check with: `{% if variable is defined %}...{% endif %}`

### GrapeJS stripping Twig code
- This shouldn't happen, but if it does, try the Code view in GrapeJS
- Ensure you're not inside an MJML component that escapes content

## How It Works

1. Plugin listens to `EMAIL_ON_SEND` and `EMAIL_ON_DISPLAY` events
2. Checks if content contains Twig syntax (`{{`, `{%`, `{#`)
3. Fixes HTML entities inside Twig tags (GrapeJS compatibility)
4. Merges API tokens + lead data into Twig context
5. Renders template through Twig engine
6. On error: logs issue, returns original content

## License

MIT License - see [LICENSE](LICENSE) file.

## Contributing

Contributions welcome! Please feel free to submit a Pull Request.

## Credits

Inspired by:
- [mautic-advanced-templates-bundle](https://github.com/Logicify/mautic-advanced-templates-bundle)
- [Mautic Forum discussion](https://forum.mautic.org/t/email-templates-how-to-use-conditionals-like-if-else/34170/2)
