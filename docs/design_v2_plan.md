# Modern WhatsApp Chatbot Builder Dashboard Implementation Plan

This plan outlines the steps to create a premium, SaaS-grade dashboard for the WAPI platform, inspired by leaders like AiSensy and WATI.

## 1. Design System & UI Foundation
- Create `assets/css/v2-dashboard.css`:
  - Modern color palette (WhatsApp Green + Slate/Zinc shades).
  - High-end typography (Inter & Plus Jakarta Sans).
  - Component-based styles: Cards, Buttons, List Items, Modals.
  - Sidebar and Topbar layouts.

## 2. Dashboard Overview (v2)
- Create `dashboard/v2-index.php`:
  - 4 major metric cards (Sent, Delivered, Read, Failed).
  - Active campaigns summary.
  - Analytics chart using Chart.js.

## 3. Visual Chatbot Flow Builder (The Core)
- Create `dashboard/v2-chatbot.php`:
  - A full-screen node-based editor interface.
  - Nodes: Start, Send Message, Image, Interactive (Buttons/Lists), Condition (Branching), Delay.
  - Visual connections with smooth curved SVG paths.
  - Zoom/Pan utility (Mocked).

## 4. Live Chat Inbox
- Create `dashboard/v2-live-chat.php`:
  - 3-column layout (Conversation List, Active Chat, Contact Info/Actions).
  - Real-time message bubbles.
  - Quick replies dropdown.

## 5. Campaigns & Templates
- Create `dashboard/v2-campaigns.php` and `dashboard/v2-templates.php`:
  - Responsive tables with status badges.
  - CSV upload modal for bulk broadcasting.
  - Template previewer.

## 6. Contacts Management
- Create `dashboard/v2-contacts.php`:
  - Table-based management with segmentation tags.

## 7. Component System
- Shared PHP layouts for Sidebar and Header (`includes/v2-header.php`, `v2-sidebar.php`, `v2-footer.php`).
