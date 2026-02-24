#!/usr/bin/env python3
import cgi
import json
import os
import urllib.request
import urllib.parse

print("Content-Type: application/json")
print()

def load_env(path):
    env = {}
    try:
        with open(path) as f:
            for line in f:
                line = line.strip()
                if '=' in line and not line.startswith('#'):
                    k, v = line.split('=', 1)
                    env[k.strip()] = v.strip()
    except:
        pass
    return env

env = load_env('/home/protected/.env')
if not env:
    env = load_env(os.path.join(os.path.dirname(os.path.dirname(__file__)), '.env'))

token   = env.get('TELEGRAM_BOT_TOKEN')
chat_id = env.get('TELEGRAM_CHAT_ID')

if not token or not chat_id:
    print(json.dumps({'success': False, 'message': 'Server configuration missing'}))
    exit()

form     = cgi.FieldStorage()
name     = form.getvalue('name', '').strip()
message  = form.getvalue('message', '').strip()
honeypot = form.getvalue('website', '')

if honeypot:
    print(json.dumps({'success': True, 'message': 'Message sent!'}))
    exit()

if not name or not message:
    print(json.dumps({'success': False, 'message': 'Name and message are required'}))
    exit()

if len(message) > 2000:
    print(json.dumps({'success': False, 'message': 'Message too long'}))
    exit()

text = f"Message from morganrivers.com\nName: {name}\n\n{message}"
data = urllib.parse.urlencode({'chat_id': chat_id, 'text': text}).encode()

try:
    req = urllib.request.Request(f"https://api.telegram.org/bot{token}/sendMessage", data=data)
    with urllib.request.urlopen(req, timeout=10) as resp:
        result = json.loads(resp.read())
    if result.get('ok'):
        print(json.dumps({'success': True, 'message': 'Message sent!'}))
    else:
        print(json.dumps({'success': False, 'message': 'Telegram API error'}))
except Exception:
    print(json.dumps({'success': False, 'message': 'Failed to send message'}))
