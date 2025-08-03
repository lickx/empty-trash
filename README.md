# Clickable terminal for emptying trash in OpenSim

This is to secure your grid users' inventories on the hypergrid.
Especially important when your grid uses hypergrid 1.0!

In robust.ini set AllowDelete to false:
```
[InventoryService]
    LocalServiceModule = "OpenSim.Services.InventoryService.dll:XInventoryService"

    ; Will calls to purge folders (empty trash) and immediately delete/update items or folders (not move to trash first) succeed?
    ; If this is set to false then some other arrangement must be made to perform these operations if necessary.
    AllowDelete = false
```

If the php script only listens on your private subnet, take note that
by default, llHttpRequest (which the terminal uses) can't request webpages
from private subnets. There are two ways to address this:

1. Insecure: Remove your private subnet from the blacklist in OpenSim.ini:
```
[Network]
; 192.168.0.0/16 removed:
OutboundDisallowForUserScripts = 0.0.0.0/8|10.0.0.0/8|100.64.0.0/10|127.0.0.0/8|169.254.0.0/16|172.16.0.0/12|192.0.0.0/24|192.0.2.0/24|192.88.99.0/24|198.18.0.0/15|198.51.100.0/24|203.0.113.0/24|224.0.0.0/4|240.0.0.0/4|255.255.255.255/32
```
Caution, this would allow any lsl script (even in a visitor's attachment) to
access any webserver in that private subnet, for example your NAS storage!

2. Secure: Use [opensim-lickx](https://github.com/lickx/opensim-lickx) instead of stock OpenSim.
This (only) allows requests from the /lslhttp folder even on blacklisted private networks.
See commit 7503d1a23fd4a45742295cdb44223d2f7ac4033a if you would like to apply this
to your own OpenSim fork.


If the php script listens on the public ip (not recommended), ALLOWED_HOST should
be filled in config.php, and the relevant section uncommented in trash.php.
