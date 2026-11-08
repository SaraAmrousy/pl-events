#!/usr/bin/env python3
"""Consistent snapshot / restore of the sandbox SQLite database (round 7).
The sandbox DB runs in WAL mode: recent writes live in .ht.sqlite-wal until a checkpoint, so copying
the main file alone is NOT a snapshot (writes "replay" after a restore — the cause of the leftovers
found in round 7). This goes through SQLite's own backup API, both ways.
  dbsnap.py save    <live.sqlite> <snapshot.sqlite>
  dbsnap.py restore <snapshot.sqlite> <live.sqlite>
"""
import sqlite3, sys, os
mode, a, b = sys.argv[1], sys.argv[2], sys.argv[3]
if mode == 'save':
    if os.path.exists(b): os.unlink(b)
    src = sqlite3.connect(a, timeout=30); dst = sqlite3.connect(b)
    src.backup(dst); dst.execute('PRAGMA journal_mode=DELETE'); dst.close(); src.close()
elif mode == 'restore':
    src = sqlite3.connect(a, timeout=30); dst = sqlite3.connect(b, timeout=30)
    src.backup(dst); dst.execute('PRAGMA wal_checkpoint(TRUNCATE)'); dst.close(); src.close()
else:
    sys.exit('usage: dbsnap.py save|restore A B')
