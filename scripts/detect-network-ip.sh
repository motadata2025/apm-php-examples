#!/bin/bash

# APM PHP Examples - Network IP Detection Script
# This script detects the actual IPv4 network interface IP

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🌐 Detecting Network Interface IP${NC}"
echo "=================================="

# Function to get primary network interface
get_primary_interface() {
    # Get the interface used for default route
    ip route | grep default | head -1 | awk '{print $5}'
}

# Function to get IP for interface
get_interface_ip() {
    local interface="$1"
    ip addr show "$interface" | grep "inet " | grep -v "127.0.0.1" | awk '{print $2}' | cut -d/ -f1 | head -1
}

# Function to get all available interfaces and IPs
get_all_interfaces() {
    echo -e "${YELLOW}Available network interfaces:${NC}"
    local count=1
    declare -A interfaces
    
    for interface in $(ip link show | grep -E "^[0-9]+:" | awk -F: '{print $2}' | tr -d ' ' | grep -v lo); do
        local ip=$(get_interface_ip "$interface")
        if [ -n "$ip" ]; then
            echo "  $count. $interface: $ip"
            interfaces[$count]="$interface:$ip"
            ((count++))
        fi
    done
    
    echo "${interfaces[@]}"
}

# Detect primary interface and IP
PRIMARY_INTERFACE=$(get_primary_interface)
PRIMARY_IP=$(get_interface_ip "$PRIMARY_INTERFACE")

echo -e "${GREEN}Primary network interface: $PRIMARY_INTERFACE${NC}"
echo -e "${GREEN}Primary IP address: $PRIMARY_IP${NC}"

# Show all available interfaces
echo ""
ALL_INTERFACES=$(get_all_interfaces)

# Interactive selection
echo ""
echo -e "${BLUE}IP Selection Options:${NC}"
echo "1. Use primary IP: $PRIMARY_IP (recommended)"
echo "2. Use localhost: 127.0.0.1 (local only)"
echo "3. Use all interfaces: 0.0.0.0 (bind to all)"
echo "4. Select specific interface"
echo "5. Enter custom IP"

while true; do
    read -p "Enter your choice (1-5): " choice
    case $choice in
        1)
            SELECTED_IP="$PRIMARY_IP"
            echo -e "${GREEN}Selected IP: $SELECTED_IP${NC}"
            break
            ;;
        2)
            SELECTED_IP="127.0.0.1"
            echo -e "${YELLOW}Selected IP: $SELECTED_IP (local access only)${NC}"
            break
            ;;
        3)
            SELECTED_IP="0.0.0.0"
            echo -e "${YELLOW}Selected IP: $SELECTED_IP (all interfaces)${NC}"
            break
            ;;
        4)
            echo ""
            get_all_interfaces > /dev/null
            echo ""
            read -p "Enter interface number: " interface_num
            
            # Parse selected interface
            local selected_interface_info=$(echo "$ALL_INTERFACES" | tr ' ' '\n' | grep "^$interface_num:" | head -1)
            if [ -n "$selected_interface_info" ]; then
                SELECTED_IP=$(echo "$selected_interface_info" | cut -d: -f3)
                echo -e "${GREEN}Selected IP: $SELECTED_IP${NC}"
                break
            else
                echo -e "${RED}Invalid interface number${NC}"
            fi
            ;;
        5)
            read -p "Enter custom IP address: " custom_ip
            # Basic IP validation
            if [[ $custom_ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
                SELECTED_IP="$custom_ip"
                echo -e "${GREEN}Selected IP: $SELECTED_IP${NC}"
                break
            else
                echo -e "${RED}Invalid IP address format${NC}"
            fi
            ;;
        *)
            echo -e "${RED}Invalid choice. Please enter 1-5.${NC}"
            ;;
    esac
done

# Update configuration file
CONFIG_FILE="config/deployment.env"
if [ -f "$CONFIG_FILE" ]; then
    # Update existing configuration
    if grep -q "^NETWORK_INTERFACE=" "$CONFIG_FILE"; then
        sed -i "s/^NETWORK_INTERFACE=.*/NETWORK_INTERFACE=$SELECTED_IP/" "$CONFIG_FILE"
    else
        echo "NETWORK_INTERFACE=$SELECTED_IP" >> "$CONFIG_FILE"
    fi
    echo -e "${GREEN}✅ Updated $CONFIG_FILE with NETWORK_INTERFACE=$SELECTED_IP${NC}"
else
    # Create new configuration
    mkdir -p config
    echo "NETWORK_INTERFACE=$SELECTED_IP" > "$CONFIG_FILE"
    echo -e "${GREEN}✅ Created $CONFIG_FILE with NETWORK_INTERFACE=$SELECTED_IP${NC}"
fi

# Display network information
echo ""
echo -e "${BLUE}📋 Network Configuration Summary:${NC}"
echo "================================="
echo "Selected IP: $SELECTED_IP"
echo "Primary Interface: $PRIMARY_INTERFACE"
echo "Configuration File: $CONFIG_FILE"

# Test connectivity
echo ""
echo -e "${BLUE}🔍 Testing Network Connectivity:${NC}"
echo "================================="

# Test if IP is reachable
if ping -c 1 -W 1 "$SELECTED_IP" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ IP $SELECTED_IP is reachable${NC}"
else
    echo -e "${YELLOW}⚠️  IP $SELECTED_IP may not be reachable${NC}"
fi

# Show what services will be accessible
echo ""
echo -e "${BLUE}🌐 Your applications will be accessible at:${NC}"
echo "=============================================="
echo "Simple PHP:      http://$SELECTED_IP:8080"
echo "Laravel:         http://$SELECTED_IP:8081"
echo "Symfony:         http://$SELECTED_IP:8082"
echo "Slim Framework:  http://$SELECTED_IP:8083"
echo "CodeIgniter:     http://$SELECTED_IP:8084"

# Security warning for public IPs
if [[ ! "$SELECTED_IP" =~ ^(127\.|10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.) ]]; then
    echo ""
    echo -e "${YELLOW}⚠️  Security Warning:${NC}"
    echo "You've selected a public IP address. Your applications will be accessible from the internet."
    echo "Make sure you have proper security measures in place:"
    echo "  • Firewall configuration"
    echo "  • Strong authentication"
    echo "  • HTTPS/SSL certificates"
    echo "  • Regular security updates"
fi

echo ""
echo -e "${GREEN}🎉 Network configuration completed!${NC}"
