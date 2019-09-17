#!/bin/bash
# 一次性处理git提交
# su liushuojia
if [ ! -n "$1" ] ;then
commit="提交"
else
commit=$1
fi

git add -A .
git commit -m "$commit"
git push
