#!/bin/bash
# Lance le serveur Symfony local + le tunnel devtunnel pour tester le callback
# Azure AD en local (voir section "Tunnel de dev (Azure AD callback)" du
# CLAUDE.md pour le contexte complet).
#
# Usage : scripts/dev-tunnel.sh
# Affiche l'URL publique HTTPS a utiliser et a enregistrer dans Azure AD.

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEVTUNNEL="$HOME/bin/devtunnel"
TUNNEL_ID="peaceful-ant-f4ndxfd.euw"
PORT=8000
LOG_FILE="$PROJECT_DIR/var/log/devtunnel.log"

cd "$PROJECT_DIR"

if [ ! -x "$DEVTUNNEL" ]; then
    echo "devtunnel introuvable (${DEVTUNNEL}). Installation :"
    echo "  curl -sL https://aka.ms/DevTunnelCliInstall | bash"
    echo "  echo 'export PATH=\"\$HOME/bin:\$PATH\"' >> ~/.zshrc && source ~/.zshrc"
    exit 1
fi

# 1. Serveur Symfony local (port 8000, HTTP simple)
if ! symfony server:status 2>&1 | grep -qi "listening"; then
    echo "Demarrage du serveur Symfony..."
    symfony server:start -d --port="$PORT"
else
    echo "Serveur Symfony deja lance."
fi

# 2. Stoppe tout devtunnel host deja en cours (ex. celui d'un autre projet,
# comme rma.lecoqsportif, qui tourne sur un autre port) avant de lancer le
# notre -- evite toute confusion entre tunnels de projets differents.
if pgrep -f "devtunnel host" > /dev/null; then
    echo "Arret du tunnel devtunnel en cours (RMA ou autre)..."
    pkill -f "devtunnel host"
    sleep 2
fi

echo "Demarrage de devtunnel host (tunnel: $TUNNEL_ID, port: $PORT)..."
mkdir -p "$(dirname "$LOG_FILE")"
: > "$LOG_FILE"
# L'ID de tunnel est un argument POSITIONNEL, pas la valeur du flag "-t"
# (qui signifie --access-token, pas "tunnel" : le passer en -t declenche
# une erreur Unauthorized puisque l'ID n'est pas un jeton d'acces valide).
# Necessaire ici : le "tunnel par defaut" est un reglage global au compte
# devtunnel (pas par projet) -- un autre projet (rma.lecoqsportif) peut
# l'avoir change en faisant son propre "devtunnel create".
nohup "$DEVTUNNEL" host "$TUNNEL_ID" > "$LOG_FILE" 2>&1 &
disown
sleep 4

# devtunnel host peut demarrer puis planter immediatement (ex. session
# expiree) : verifier qu'il tourne encore avant d'annoncer un succes.
if ! pgrep -f "devtunnel host" > /dev/null; then
    echo ""
    echo "ECHEC : devtunnel host s'est arrete immediatement. Log :"
    echo "---"
    cat "$LOG_FILE"
    echo "---"
    if grep -qi "unauthorized" "$LOG_FILE"; then
        echo ""
        echo "Session expiree -> reconnecte-toi puis relance ce script :"
        echo "  devtunnel user login"
    fi
    exit 1
fi

# 3. Extraction de l'URL publique. "devtunnel show" cible explicitement
# $TUNNEL_ID (argument positionnel) : sans ca, il affiche le "dernier tunnel
# utilise" au niveau du compte (reglage global, pas par projet) -- qui peut
# etre celui d'un autre projet (rma.lecoqsportif) si lance plus recemment.
URL=$("$DEVTUNNEL" show "$TUNNEL_ID" 2>/dev/null | grep -oE 'https://[a-z0-9.-]+\.devtunnels\.ms' | head -1)

if [ -z "$URL" ]; then
    echo "URL non trouvee. Verifie l'etat du tunnel :"
    echo "  $DEVTUNNEL show"
    echo "  cat $LOG_FILE"
    exit 1
fi

echo ""
echo "URL publique (a utiliser, a enregistrer dans Azure AD si elle a change) :"
echo "  $URL/fr/"
echo ""
echo "Callback Azure AD attendu : $URL/callback"
echo ""
echo "Note : ce script a stoppe le tunnel RMA s'il tournait. Pour le relancer :"
echo "  cd ../rma.lecoqsportif && bash scripts/dev-tunnel.sh"
