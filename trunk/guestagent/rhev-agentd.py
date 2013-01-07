#!/usr/bin/python
#
# Copyright 2010 Red Hat, Inc. and/or its affiliates.
#
# Licensed to you under the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.  See the files README and
# LICENSE_GPL_v2 which accompany this distribution.
#

import logging, logging.config, os, time, signal, sys
import ConfigParser
import utils
from qGuestAgentLinux2 import LinuxVdsAgent

RHEV_AGENT_CONFIG = '/etc/rhev-agent.conf'
RHEV_AGENT_PIDFILE = '/var/run/rhev-agent/rhev-agentd.pid'

class RHEVAgentDaemon:

    def __init__(self):
        logging.config.fileConfig(RHEV_AGENT_CONFIG)

    def run(self):
        logging.info("Starting RHEV-Agent daemon")

        config = ConfigParser.ConfigParser()
        config.read(RHEV_AGENT_CONFIG)

        self.agent = LinuxVdsAgent(config)
        
        utils.createDaemon(True)
        file(RHEV_AGENT_PIDFILE, "w").write("%s\n" % (os.getpid()))
        os.chmod(RHEV_AGENT_PIDFILE, 0x1b4) # rw-rw-r-- (664)
        
        self.register_signal_handler()
        self.agent.run()

        logging.info("RHEV-Agent daemon is down.")

    def register_signal_handler(self):
        
        def sigterm_handler(signum, frame):
            logging.debug("Handling signal %d" % (signum))
            if signum == signal.SIGTERM:
                logging.info("Stopping RHEV-Agent daemon")
                self.agent.stop()
 
        signal.signal(signal.SIGTERM, sigterm_handler)

if __name__ == '__main__':
    try:
        try:
            agent = RHEVAgentDaemon()
            agent.run()
        except:
            logging.exception("Unhandled exception in RHEV-Agent daemon!")
            sys.exit(1)
    finally:
        utils.rmFile(RHEV_AGENT_PIDFILE)
