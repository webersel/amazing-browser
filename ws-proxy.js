const WebSocket = require('ws');
const http = require('http');

const server = http.createServer();
const wss = new WebSocket.Server({ server });

wss.on('connection', ws => {
    ws.on('message', message => {
        const targetUrl = message.toString();
        const targetWs = new WebSocket(targetUrl);

        targetWs.on('open', () => console.log(`Connected to ${targetUrl}`));
        targetWs.on('message', data => ws.send(data));
        targetWs.on('close', () => ws.close());
        targetWs.on('error', err => {
            console.error(`Error: ${err.message}`);
            ws.close();
        });

        ws.on('message', data => targetWs.send(data));
    });

    ws.on('close', () => console.log('Client disconnected'));
});

server.listen(process.env.PORT || 8080, () => console.log('WebSocket proxy running'));