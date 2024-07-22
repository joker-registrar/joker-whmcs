#!/bin/bash

if [ -z $1 ] ; then
 echo "usage: $0 <version>"
 echo "       creates a zip package to provide for customers"
 echo "       <version> is git version tag of the intended release"
 exit
fi

FILE="whmcs-joker-registrar-module"
BUILD=$(basename "$PWD")
VERSION=`git tag|tail -1`
REQUESTED=$1
if [ "$BUILD" != "build" ] ; then
 echo "please run this in build directory"
 exit 1
fi

if [ "$VERSION" != "$REQUESTED" ] ; then
 echo "warning, $REQUESTED is not the latest version: ${VERSION} != $REQUESTED"
fi

git checkout tags/$REQUESTED


echo "creating package: ${FILE}-${REQUESTED}.zip"
pushd ..
zip -r ${BUILD}/${FILE}-${REQUESTED}.zip . -i@${BUILD}/package.lst
popd

echo "done."
