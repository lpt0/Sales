#! /bin/bash
# Push working files to opentech
rm htdocs/*_log.txt 2>/dev/null
scp -r htdocs/* opentech:./htdocs/
