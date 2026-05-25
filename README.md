# CakePHP Reactions Plugin

[![CI](https://github.com/dereuromark/cakephp-reactions/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-reactions/actions?query=workflow%3ACI+branch%3Amaster)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-reactions/master.svg)](https://app.codecov.io/github/dereuromark/cakephp-reactions/tree/master)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-reactions/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-reactions)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-reactions/license.png)](https://packagist.org/packages/dereuromark/cakephp-reactions)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-reactions/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-reactions)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)

Reactions plugin for CakePHP applications.

This is the multiple-reactions-per-entity companion to
[dereuromark/cakephp-favorites](https://github.com/dereuromark/cakephp-favorites),
which handles the single-opinion star/like/favorite use case. For ratings, use
[dereuromark/cakephp-ratings](https://github.com/dereuromark/cakephp-ratings).

Each reaction row stores a string reaction key in the `reaction` column. That key can be
a literal emoji like `👍` or a named key like `thumbsup`. Set `allowed` if you want to
restrict the accepted set.

## Install, Setup, Usage

See the **[Docs](docs/README.md)** for details.
