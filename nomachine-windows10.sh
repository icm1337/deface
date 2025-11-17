#!/bin/bash

function goto() {
    label=$1
    cd
    cmd=$(sed -n "/^:[[:blank:]]*${label}/{:a;n;p;ba};" $0 | grep -v ':$')
    eval "$cmd"
    exit
}

: serveo
clear
echo "======================="
echo "Using Serveo instead of Ngrok (Free, No Token)"
echo "======================="

echo "Creating TCP tunnel on Serveo..."
# Start Serveo tunnel
ssh -o StrictHostKeyChecking=no -R 4000:localhost:4000 serveo.net > serveo.log 2>&1 &

# Wait for Serveo to start
sleep 3

# Verify tunnel created
if grep -q "Forwarding" serveo.log; then
    echo "Serveo Tunnel OK!"
else
    echo "Serveo Error! Retrying..."
    sleep 1
    goto serveo
fi

# Extract public TCP address
PUBLIC_URL=$(grep -o "[a-zA-Z0-9.-]*serveo.net:[0-9]*" serveo.log)

clear
echo "======================="
echo "Starting NoMachine Ubuntu Desktop..."
echo "======================="

docker run --rm -d --network host --privileged --name nomachine-xfce4 \
    -e PASSWORD=123456 -e USER=user \
    --cap-add=SYS_PTRACE --shm-size=1g \
    thuonghai2711/nomachine-ubuntu-desktop:windows10

clear
echo "======================="
echo " NoMachine Information "
echo "======================="
echo "Download NoMachine: https://www.nomachine.com/download"
echo
echo "Public TCP Address:"
echo "$PUBLIC_URL"
echo
echo "User: user"
echo "Password: 123456"
echo
echo "If VM can't connect: Restart Cloud Shell and re-run script."
echo
echo "Tunnel stays alive for 12 hours."

# Keep script running (12 hours)
seq 1 43200 | while read i; do 
    echo -en "\r Running .     $i s /43200 s"
    sleep 0.1
    echo -en "\r Running ..    $i s /43200 s"
    sleep 0.1
    echo -en "\r Running ...   $i s /43200 s"
    sleep 0.1
    echo -en "\r Running ....  $i s /43200 s"
    sleep 0.1
    echo -en "\r Running ..... $i s /43200 s"
    sleep 0.1
    echo -en "\r Running     . $i s /43200 s"
    sleep 0.1
    echo -en "\r Running  .... $i s /43200 s"
    sleep 0.1
    echo -en "\r Running   ... $i s /43200 s"
    sleep 0.1
    echo -en "\r Running    .. $i s /43200 s"
    sleep 0.1
    echo -en "\r Running     . $i s /43200 s"
    sleep 0.1
done
