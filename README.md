# CultureSpace

[![Symfony](https://img.shields.io/badge/Symfony-6.x-brightgreen.svg)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.x-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

**CultureSpace** is a modern web application built with Symfony, designed to provide a marketplace and community platform with features like product listings, dynamic pricing, voting, and event management. It features a sleek, green-themed UI with animations and a responsive design.

## Table of Contents
- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Contributing](#contributing)
- [License](#license)
- [Acknowledgments](#acknowledgments)

## Features
- **Product Management**: Create, edit, delete, and list products with dynamic pricing and voting.
- **User Authentication**: Secure signup and login for regular users and admins with reCAPTCHA protection.
- **Marketplace**: Browse products with filters (name, price, category, availability, discount).
- **Community**: Group listings and interactions (in development).
- **Events**: Event management (in development).
- **PDF Export**: Generate product lists as PDFs using `wkhtmltopdf`.
- **Modern UI**: Green-themed design with animations (fade-ins, bounces) and responsive layouts.
- **Custom 404 Page**: Animated green-themed error page.

## Prerequisites
- **PHP**: 8.0 or higher
- **Composer**: Latest version
- **Symfony CLI**: For running the server
- **MySQL**: 5.7 or higher (or another Doctrine-supported DB)
- **wkhtmltopdf**: For PDF generation (optional)
- **Node.js/NPM**: For asset compilation (optional, if using Webpack Encore)

## Installation
1. **Clone the Repository**:
   ```bash
   git@github.com:MDadem/pi_project.git
   cd pi_project
