#!/usr/bin/python3
# coding: utf-8
import psutil
import sys

def memory_usage():
    # Get the memory usage in bytes
    mem = psutil.virtual_memory()

    # total_memory = mem.total
    # used_memory = mem.used
    # free_memory = mem.free
    # memory_percent = mem.percent

    return mem.percent

try:
    print(memory_usage())
except Exception as e:
    print("Error while getting memory usage:", e)
    sys.exit(1)

sys.exit(0)