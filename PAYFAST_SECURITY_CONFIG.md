# PayFast Security Configuration

**IMPORTANT**: For production security, only allow PayFast webhook notifications from official PayFast server IPs.

## Official PayFast Server IPs

PayFast webhooks will only come from these IP addresses:

```
197.97.145.144/28 (197.97.145.144 - 197.97.145.159)
41.74.179.192/27 (41.74.179.192 - 41.74.179.223)
102.216.36.0/28 (102.216.36.0 - 102.216.36.15)
102.216.36.128/28 (102.216.36.128 - 102.216.36.143)
144.126.193.139
```

## Configuration Examples

### Option 1: Apache (.htaccess or VirtualHost)

**Recommended**: Add to your Apache VirtualHost configuration or create in `public/.htaccess`

```apache
# PayFast webhook IP whitelist
<Location /payment/webhook>
    Order Deny,Allow
    Deny from all

    # PayFast IP ranges
    Allow from 197.97.145.144/28
    Allow from 41.74.179.192/27
    Allow from 102.216.36.0/28
    Allow from 102.216.36.128/28
    Allow from 144.126.193.139
</Location>
```

**Alternative**: Using Apache 2.4+ syntax

```apache
# PayFast webhook IP whitelist
<Location /payment/webhook>
    Require ip 197.97.145.144/28
    Require ip 41.74.179.192/27
    Require ip 102.216.36.0/28
    Require ip 102.216.36.128/28
    Require ip 144.126.193.139
</Location>
```

### Option 2: Nginx

Add to your Nginx server block:

```nginx
# PayFast webhook IP whitelist
location /payment/webhook {
    # Only allow PayFast server IPs
    allow 197.97.145.144/28;
    allow 41.74.179.192/27;
    allow 102.216.36.0/28;
    allow 102.216.36.128/28;
    allow 144.126.193.139;
    deny all;

    # Standard Laravel routing
    try_files $uri $uri/ /index.php?$query_string;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Option 3: Server Firewall (UFW)

**Additional layer of security at firewall level:**

```bash
# Allow PayFast IPs to HTTPS port
sudo ufw allow from 197.97.145.144/28 to any port 443 comment 'PayFast webhook'
sudo ufw allow from 41.74.179.192/27 to any port 443 comment 'PayFast webhook'
sudo ufw allow from 102.216.36.0/28 to any port 443 comment 'PayFast webhook'
sudo ufw allow from 102.216.36.128/28 to any port 443 comment 'PayFast webhook'
sudo ufw allow from 144.126.193.139 to any port 443 comment 'PayFast webhook'

# Reload firewall
sudo ufw reload

# Check rules
sudo ufw status numbered
```

### Option 4: Cloudflare WAF (If using Cloudflare)

**Firewall Rule in Cloudflare Dashboard:**

1. Go to **Security** → **WAF** → **Create Firewall Rule**
2. Name: "PayFast Webhook IP Whitelist"
3. Expression Builder:
   ```
   (http.request.uri.path eq "/payment/webhook" and
    not ip.src in {
      197.97.145.144/28
      41.74.179.192/27
      102.216.36.0/28
      102.216.36.128/28
      144.126.193.139
    })
   ```
4. Action: **Block**

## Testing IP Whitelist

After configuring, test that:

1. ✅ **PayFast webhooks work** - Make a real payment and verify webhook is received
2. ✅ **Other IPs are blocked** - Try accessing `/payment/webhook` from your own IP (should get 403)

### Test Command
```bash
# Should return 403 Forbidden (from non-PayFast IP)
curl https://yourdomain.com/payment/webhook

# From PayFast servers, it should work (you can't test this directly)
```

## Security Layers

Your PayFast webhook security has multiple layers:

1. **IP Whitelist** (this configuration) - Only PayFast IPs allowed
2. **Signature Validation** (in code) - Verifies PayFast signature with passphrase
3. **HTTPS** (SSL) - Encrypted connection
4. **CSRF Exemption** (Laravel) - Webhook route excluded from CSRF

All layers work together for maximum security.

## Maintenance

PayFast may add new server IPs in the future. Check their documentation periodically:
- https://developers.payfast.co.za/docs

If PayFast announces new IPs, add them to your whitelist configuration.

## Troubleshooting

**Problem**: Webhook not working after IP whitelist
- Check PayFast dashboard for webhook errors
- Verify IP ranges are correct (no typos)
- Check server logs: `tail -f /var/log/apache2/error.log` or `/var/log/nginx/error.log`
- Temporarily disable IP whitelist to test if that's the issue

**Problem**: 403 Forbidden on webhook
- Verify you copied IP ranges correctly
- Check web server configuration syntax
- Restart web server after config changes:
  ```bash
  sudo systemctl restart apache2
  # or
  sudo systemctl restart nginx
  ```

## Recommended Configuration

**Best Practice**: Use web server level (Apache/Nginx) IP whitelisting.

- ✅ Fast (no PHP processing)
- ✅ Reliable (blocks before hitting application)
- ✅ Easy to maintain
- ✅ Standard security practice

The application already validates PayFast signatures, so IP whitelisting is an additional security layer.

---

**Status**: Configured ☐

**Tested**: Webhook working ☐

**Date Configured**: ________________

**Configured By**: ________________
