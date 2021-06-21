#!/usr/bin/env python
import socket
import os
TCP_IP = '127.0.0.1'
TCP_PORT = 5678
SOCKET="/run/user/%d/php-lrpm/socket" % os.geteuid()
BUFFER_SIZE = 1024
msg = "status" 

for i in range(0, 1000):
  s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
  s.connect(SOCKET)
  s.send(msg.encode('utf-8'))
  data = s.recv(BUFFER_SIZE)
  s.close()
  print("received data: %s" % data)
