#!/bin/bash

# Network Manager - Dynamic IP detection and management
# APM PHP Examples - Laravel Application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration files
CONFIG_DIR="config"
NETWORK_STATE_FILE="$CONFIG_DIR/network.state"
APP_CONFIG_FILE="$CONFIG_DIR/app.env"

# Ensure config directory exists
mkdir -p "$CONFIG_DIR"

# Function to get current machine IP (cross-distribution compatible)
get_current_machine_ip() {
    # Try multiple methods to get the primary IP
    local ip=""

    # Method 1: ip route (most reliable on modern Linux)
    if command -v ip >/dev/null 2>&1; then
        # Try different variations for different distributions
        ip=$(ip route get 8.8.8.8 2>/dev/null | grep -oE 'src [0-9.]+' | awk '{print $2}' | head -1)
        if [ -z "$ip" ]; then
            ip=$(ip route get 1.1.1.1 2>/dev/null | grep -oE 'src [0-9.]+' | awk '{print $2}' | head -1)
        fi
    fi

    # Method 2: hostname -I (works on most distributions)
    if [ -z "$ip" ] && command -v hostname >/dev/null 2>&1; then
        ip=$(hostname -I 2>/dev/null | awk '{print $1}')
    fi

    # Method 3: ifconfig (legacy but widely available)
    if [ -z "$ip" ] && command -v ifconfig >/dev/null 2>&1; then
        # Different ifconfig output formats across distributions
        ip=$(ifconfig 2>/dev/null | grep -E 'inet (addr:)?[0-9]' | grep -v '127.0.0.1' | sed -E 's/.*inet (addr:)?([0-9.]+).*/\2/' | head -1)
    fi

    # Method 4: /proc/net/route + ip addr (universal fallback)
    if [ -z "$ip" ] && [ -f /proc/net/route ]; then
        local interface=$(awk '$2 == 00000000 { print $1 }' /proc/net/route | head -1)
        if [ -n "$interface" ] && command -v ip >/dev/null 2>&1; then
            ip=$(ip addr show "$interface" 2>/dev/null | grep -oE 'inet [0-9.]+' | awk '{print $2}' | head -1)
        fi
    fi

    # Method 5: /sys/class/net approach (for embedded systems)
    if [ -z "$ip" ] && [ -d /sys/class/net ]; then
        for interface in /sys/class/net/*/; do
            local iface=$(basename "$interface")
            if [ "$iface" != "lo" ] && [ -f "/sys/class/net/$iface/operstate" ]; then
                local state=$(cat "/sys/class/net/$iface/operstate" 2>/dev/null)
                if [ "$state" = "up" ] && command -v ip >/dev/null 2>&1; then
                    ip=$(ip addr show "$iface" 2>/dev/null | grep -oE 'inet [0-9.]+' | awk '{print $2}' | head -1)
                    [ -n "$ip" ] && break
                fi
            fi
        done
    fi

    # Method 6: curl/wget external IP (last resort for NAT environments)
    if [ -z "$ip" ]; then
        if command -v curl >/dev/null 2>&1; then
            ip=$(curl -s --connect-timeout 5 ifconfig.me 2>/dev/null || curl -s --connect-timeout 5 ipinfo.io/ip 2>/dev/null)
        elif command -v wget >/dev/null 2>&1; then
            ip=$(wget -qO- --timeout=5 ifconfig.me 2>/dev/null || wget -qO- --timeout=5 ipinfo.io/ip 2>/dev/null)
        fi
    fi

    # Validate IP format
    if [[ "$ip" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        echo "$ip"
    else
        echo "127.0.0.1"  # Safe fallback
    fi
}

# Function to get saved network configuration
get_saved_network_config() {
    if [ -f "$NETWORK_STATE_FILE" ]; then
        grep "CONFIGURED_IP=" "$NETWORK_STATE_FILE" 2>/dev/null | cut -d'=' -f2 || echo ""
    else
        echo ""
    fi
}

# Function to get network interface name
get_primary_interface() {
    # Get the interface used for default route
    if command -v ip >/dev/null 2>&1; then
        ip route | grep default | awk '{print $5}' | head -1
    elif [ -f /proc/net/route ]; then
        awk '$2 == 00000000 { print $1 }' /proc/net/route | head -1
    else
        echo "eth0"  # fallback
    fi
}

# Function to check if IP has changed
check_ip_change() {
    local current_ip=$(get_current_machine_ip)
    local saved_ip=$(get_saved_network_config)
    
    if [ -n "$saved_ip" ] && [ "$current_ip" != "$saved_ip" ]; then
        echo "changed"
        return 0
    elif [ -n "$saved_ip" ] && [ "$current_ip" = "$saved_ip" ]; then
        echo "same"
        return 0
    else
        echo "new"
        return 0
    fi
}

# Function to show network status
show_network_status() {
    echo -e "\n${PURPLE}🌐 Network Status${NC}"
    echo -e "=================="
    
    local current_ip=$(get_current_machine_ip)
    local saved_ip=$(get_saved_network_config)
    local interface=$(get_primary_interface)
    local ip_status=$(check_ip_change)
    
    echo -e "${BLUE}Current network information:${NC}"
    echo -e "  ${GREEN}Primary Interface: $interface${NC}"
    echo -e "  ${GREEN}Current IP: $current_ip${NC}"
    
    if [ -n "$saved_ip" ]; then
        echo -e "  ${GREEN}Configured IP: $saved_ip${NC}"
        
        case $ip_status in
            "same")
                echo -e "  ${GREEN}✅ IP address is stable${NC}"
                ;;
            "changed")
                echo -e "  ${RED}⚠️  IP address has changed!${NC}"
                echo -e "  ${YELLOW}    Previous: $saved_ip${NC}"
                echo -e "  ${YELLOW}    Current:  $current_ip${NC}"
                echo -e "  ${YELLOW}    Action required: Update configuration${NC}"
                ;;
        esac
    else
        echo -e "  ${YELLOW}⚠️  No IP configuration saved${NC}"
    fi
    
    # Check if application is configured for public access
    if [ -f "$APP_CONFIG_FILE" ]; then
        local network_interface=$(grep "NETWORK_INTERFACE=" "$APP_CONFIG_FILE" 2>/dev/null | cut -d'=' -f2 || echo "")
        if [ "$network_interface" = "0.0.0.0" ]; then
            echo -e "\n${BLUE}Application network configuration:${NC}"
            echo -e "  ${GREEN}Binding: 0.0.0.0 (all interfaces)${NC}"
            echo -e "  ${GREEN}Public access: Enabled${NC}"
            if [ "$ip_status" = "changed" ]; then
                echo -e "  ${YELLOW}⚠️  Update DNS/firewall rules for new IP${NC}"
            fi
        fi
    fi
}

# Function to get network options with dynamic IP detection
get_network_options() {
    local current_ip=$(get_current_machine_ip)
    local saved_ip=$(get_saved_network_config)
    local ip_status=$(check_ip_change)
    
    echo -e "\n${PURPLE}🌐 Network Configuration${NC}"
    
    # Show IP change warning if applicable
    if [ "$ip_status" = "changed" ]; then
        echo -e "${YELLOW}⚠️  WARNING: IP address has changed${NC}"
        echo -e "  ${YELLOW}Previous: $saved_ip${NC}"
        echo -e "  ${YELLOW}Current:  $current_ip${NC}"
        echo -e ""
    fi
    
    echo -e "  ${BLUE}Available network options:${NC}"
    echo -e "    1) localhost (127.0.0.1) - Local access only"
    echo -e "    2) public ($current_ip) - Internet accessible ${RED}[IP WILL CHANGE!]${NC}"
    echo -e "    3) all interfaces (0.0.0.0) - Bind to all interfaces ${GREEN}[RECOMMENDED - Survives IP changes]${NC}"
    echo -e "    4) custom - Specify custom IP"
    echo ""

    echo -e "  ${YELLOW}⚠️  IMPORTANT: Your IP ($current_ip) will change when:${NC}"
    echo -e "     • Machine restarts"
    echo -e "     • WiFi reconnects"
    echo -e "     • Network changes"
    echo -e "     • DHCP lease renews"
    echo ""
    echo -e "  ${GREEN}💡 RECOMMENDATION: Use option 3 (0.0.0.0) for production${NC}"
    echo -e "     This binds to ALL interfaces and survives IP changes"
    echo ""
}

# Function to save network state
save_network_state() {
    local selected_option=$1
    local selected_ip=$2
    local current_ip=$(get_current_machine_ip)
    local interface=$(get_primary_interface)
    
    cat > "$NETWORK_STATE_FILE" << EOF
# Network State - Generated on $(date)
CONFIGURED_IP=$current_ip
SELECTED_OPTION=$selected_option
SELECTED_IP=$selected_ip
PRIMARY_INTERFACE=$interface
LAST_UPDATED=$(date '+%Y-%m-%d %H:%M:%S')
EOF
    
    echo -e "  ${GREEN}✅ Network state saved${NC}"
}

# Function to update configuration after IP change
update_ip_configuration() {
    local new_ip=$(get_current_machine_ip)
    
    echo -e "\n${PURPLE}🔄 Updating IP Configuration${NC}"
    echo -e "==============================="
    
    # Update network state
    if [ -f "$NETWORK_STATE_FILE" ]; then
        sed -i "s/CONFIGURED_IP=.*/CONFIGURED_IP=$new_ip/" "$NETWORK_STATE_FILE"
        sed -i "s/LAST_UPDATED=.*/LAST_UPDATED=$(date '+%Y-%m-%d %H:%M:%S')/" "$NETWORK_STATE_FILE"
    fi
    
    # Update app configuration if it exists
    if [ -f "$APP_CONFIG_FILE" ]; then
        sed -i "s/PUBLIC_IP=.*/PUBLIC_IP=$new_ip/" "$APP_CONFIG_FILE"
        echo -e "  ${GREEN}✅ Application configuration updated${NC}"
    fi
    
    echo -e "  ${GREEN}✅ IP configuration updated to: $new_ip${NC}"
    echo -e "  ${YELLOW}⚠️  Restart application to apply changes${NC}"
}

# Function to validate network connectivity
validate_network() {
    local ip=$1
    local port=${2:-8000}
    
    echo -e "\n${PURPLE}🔍 Network Validation${NC}"
    echo -e "====================="
    
    # Check if IP is reachable
    if [ "$ip" != "127.0.0.1" ] && [ "$ip" != "0.0.0.0" ]; then
        if ping -c 1 -W 2 "$ip" >/dev/null 2>&1; then
            echo -e "  ${GREEN}✅ IP $ip is reachable${NC}"
        else
            echo -e "  ${YELLOW}⚠️  IP $ip may not be reachable from external networks${NC}"
        fi
    fi
    
    # Check if port is available
    if command -v netstat >/dev/null 2>&1; then
        if netstat -tuln 2>/dev/null | grep -q ":$port "; then
            echo -e "  ${YELLOW}⚠️  Port $port is already in use${NC}"
            return 1
        else
            echo -e "  ${GREEN}✅ Port $port is available${NC}"
        fi
    elif command -v ss >/dev/null 2>&1; then
        if ss -tuln 2>/dev/null | grep -q ":$port "; then
            echo -e "  ${YELLOW}⚠️  Port $port is already in use${NC}"
            return 1
        else
            echo -e "  ${GREEN}✅ Port $port is available${NC}"
        fi
    fi
    
    return 0
}

# Function to get recommended network configuration
get_recommended_config() {
    local current_ip=$(get_current_machine_ip)
    local saved_ip=$(get_saved_network_config)
    
    # If IP has changed or no previous config, recommend 0.0.0.0
    if [ -z "$saved_ip" ] || [ "$current_ip" != "$saved_ip" ]; then
        echo "0.0.0.0"
    else
        echo "$current_ip"
    fi
}

# Main execution
main() {
    local action=${1:-"status"}
    local ip=${2:-""}
    local port=${3:-"8000"}
    
    case $action in
        "status")
            show_network_status
            ;;
        "options")
            get_network_options
            ;;
        "save")
            save_network_state "$ip" "$port"
            ;;
        "update")
            update_ip_configuration
            ;;
        "validate")
            validate_network "$ip" "$port"
            ;;
        "current-ip")
            get_current_machine_ip
            ;;
        "check-change")
            check_ip_change
            ;;
        "recommend")
            get_recommended_config
            ;;
        *)
            echo -e "${YELLOW}Usage: $0 {status|options|save|update|validate|current-ip|check-change|recommend} [ip] [port]${NC}"
            echo -e ""
            echo -e "Commands:"
            echo -e "  status      - Show network status"
            echo -e "  options     - Show network configuration options"
            echo -e "  save        - Save network state"
            echo -e "  update      - Update IP configuration after change"
            echo -e "  validate    - Validate network connectivity"
            echo -e "  current-ip  - Get current machine IP"
            echo -e "  check-change- Check if IP has changed"
            echo -e "  recommend   - Get recommended configuration"
            ;;
    esac
}

# Execute main function
main "$@"
