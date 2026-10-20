#!/bin/sh
# Attend qu'une adresse IPv4 soit posée sur une interface, puis rend la main.
#
# Usage : wait-ip.sh [adresse] [délai en secondes] [interface]
#         (défauts : 192.168.1.29, 60, ens18)
#
# Installé par deploy/install-vm.sh dans /usr/local/sbin et appelé en ExecStartPre par le drop-in
# systemd de docker.service (docs/deploiement/apps-dev.md, section 9.1) : avec ifupdown + dhcpcd,
# network-online.target est atteint avant que l'IPv4 DHCP soit posée (ARP probing, environ 3 s) ;
# Docker relancerait alors app avant que l'adresse publiée par compose.prod.yaml existe.
#
# Sort toujours en 0, même si l'adresse n'apparaît pas : on ne bloque pas le démarrage de Docker.
# Sans dépendance au dépôt (pas de lib.sh) : le fichier est copié hors du clone.
addr=${1:-192.168.1.29}
max=${2:-60}
iface=${3:-ens18}
i=0
while [ "$i" -lt "$max" ]; do
  if ip -4 -o addr show dev "$iface" | grep -qF "$addr/"; then
    echo "wait-ip: $addr present after ${i}s"
    exit 0
  fi
  i=$((i + 1))
  sleep 1
done
echo "wait-ip: $addr absent after ${max}s, continuing"
exit 0
