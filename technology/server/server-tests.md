# Server Tests

> [!NOTE]  
> Last update: 2026-02-07

```.sh
# Settings
server_ip="100.00.000.01"
domain="website.com"
```

```.sh
# Install packages
sudo apt install dnsutils nikto nmap sqlmap sslscan sublist3r -y
```

```.sh
# Quick port scan to identify open services and their versions on the domain
nmap -F -sV $domain

# Direct scan of origin server IP (bypasses any CDN/proxy)
nmap -sV $server_ip

# Automated SQL injection vulnerability testing across forms and parameters
sqlmap -u "https://$domain" --batch --crawl=3 --forms --random-agent --level=2 --risk=1

# Comprehensive web application vulnerability scanner checking for common security issues
nikto -h "https://$domain" -Display 1234 -o nikto_report.html -Format htm

# SSL/TLS configuration analysis checking cipher strength and protocol versions
sslscan $domain

# Check HTTP security headers (HSTS, CSP, X-Frame-Options, etc.) (Note: Cloudflare may strip some headers)
curl -I "https://$domain" | grep -E "Strict-Transport-Security|Content-Security-Policy|X-Frame-Options|X-Content-Type-Options"

# Test for clickjacking vulnerability (Note: Cloudflare may strip some headers)
curl -I "https://$domain" | grep "X-Frame-Options"

# Check for exposed sensitive files and directories
nmap --script http-enum $domain

# DNS security check (DNSSEC validation)
dig $domain +dnssec @1.1.1.1

# Check for subdomain enumeration (reconnaissance)
sublist3r -d $domain
```
