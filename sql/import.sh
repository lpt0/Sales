#! /bin/bash
psql -d prod_db -f users.sql -f clients.sql -f calls.sql