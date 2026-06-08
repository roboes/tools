# WireGuard

> [!NOTE]  
> Last update: 2026-06-01

## Installation

> Notes: No need to create a sub-server (e.g. `vpn.website.com`) in Virtualmin. The VPN runs independently on a DNS-only Cloudflare record.

```.sh
sudo apt install -y wireguard
```

### Generate server keys

```.sh
# Generate server private key and save it
SERVER_PRIVATE_KEY=$(wg genkey | tee /etc/wireguard/server_private.key)

# Generate server public key from the private key
SERVER_PUBLIC_KEY=$(echo $SERVER_PRIVATE_KEY | wg pubkey | tee /etc/wireguard/server_public.key)
```

> Keys are now stored in `/etc/wireguard/server_private.key` and `/etc/wireguard/server_public.key`

### Create WireGuard server config

```.sh
# Create wg0.conf with server interface and listen port
cat <<EOF > "/etc/wireguard/wg0.conf"
[Interface]
Address = 10.6.0.1/24
ListenPort = 51820
PrivateKey = $SERVER_PRIVATE_KEY
MTU = 1420
EOF
```

### Enable IP forwarding

```.sh
# Create a dedicated configuration file for WireGuard forwarding
echo "net.ipv4.ip_forward=1" | sudo tee /etc/sysctl.d/99-wireguard-forwarding.conf > /dev/null

# Load all settings from /etc/sysctl.conf and /etc/sysctl.d/
sudo sysctl --system

# Verify that IP forwarding is enabled
sysctl net.ipv4.ip_forward
# Output should be: net.ipv4.ip_forward = 1
```

### Configure firewall

```.sh
# Open UDP port 51820 in the "public" zone permanently
sudo firewall-cmd --zone=public --add-port=51820/udp --permanent

# Add the WireGuard interface to the "trusted" zone
sudo firewall-cmd --zone=trusted --add-interface=wg0 --permanent

# Enable masquerading (NAT) for outgoing VPN traffic
sudo firewall-cmd --zone=public --add-masquerade --permanent

# Reload firewall to apply changes
sudo firewall-cmd --reload

# Show active rules
sudo firewall-cmd --list-all
```

### Generate client keys

```.sh
# Generate client private key
CLIENT_PRIVATE_KEY=$(wg genkey | tee /etc/wireguard/client1_private.key)

# Generate client public key
CLIENT_PUBLIC_KEY=$(echo $CLIENT_PRIVATE_KEY | wg pubkey | tee /etc/wireguard/client1_public.key)
```

> Keys are stored in `client1_private.key` and `client1_public.key`

### Add Client Peer to server config

```.sh
# Append client peer block to server configuration
cat <<EOF >> /etc/wireguard/wg0.conf
# Client 1 configuration
[Peer]
# Public key of client device
PublicKey = $CLIENT_PUBLIC_KEY
# IP assigned to client inside VPN
AllowedIPs = 10.6.0.2/32, 192.168.178.0/24
PersistentKeepAline = 25
EOF
```

### Start WireGuard

```.sh
# Enable WireGuard to start on boot and start now
sudo systemctl enable --now wg-quick@wg0

# Verify server status
sudo wg show
```

```.sh
echo "Client private key: $CLIENT_PRIVATE_KEY"
echo "Server public key: $SERVER_PUBLIC_KEY"
```

### DNS

Create a DNS record in Cloudflare:

| Setting      | Value          |
| ------------ | -------------- |
| Type         | A              |
| Name         | vpn            |
| Proxy status | Off (DNS only) |

### Client configuration

```.conf
[Interface]
PrivateKey = $CLIENT_PRIVATE_KEY
Address = 10.6.0.2/32
# DNS = 1.1.1.1
MTU = 1420

[Peer]
PublicKey = $SERVER_PUBLIC_KEY
Endpoint = vpn.website.com:51820
# Routes all traffic through VPN
# AllowedIPs = 0.0.0.0/0
# Routes only home network traffic
AllowedIPs = 10.6.0.0/24
PersistentKeepalive = 25
```

### Test

```.sh
# Find interface name
ip link show

# Test handshake
sudo wg show

# Monitor ICMP traffic on the physical interface
tcpdump -i eth0 icmp
```
