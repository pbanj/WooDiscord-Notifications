# WooDiscord-Notifications
Made with gemini. A php snippet for sending discord messages via webhooks on order changes.

Designed to keep you instantly updated on store activity without cluttering your inbox or requiring you to constantly refresh the WordPress dashboard. It pushes clean, formatted embed messages to your target channel whenever an order is placed or updated.

## Key Features

* **Real-Time Webhook Alerts:** Instantly pushes notifications to Discord using native webhooks.
* **Rich Embed Formatting:** Delivers clean, easy-to-read Discord embeds containing essential at-a-glance details, including Order ID, Customer Name, Order Total, and Item Count.
* **Status Tracking:** Triggers alerts for both new orders and status updates (e.g., when an order is marked as "Completed" or "Processing").
* **Admin UI Dashboard:** Manage your Discord Webhook URL securely from a dedicated settings page within the WooCommerce dashboard.
* **Privacy Friendly:** Control what information gets sent with each message.



## Installation & Usage

1. Go to your Discord server settings, navigate to **Integrations > Webhooks**, and create a new webhook/s for your desired notification channel/s. Copy the Webhook URL/s.
2. Copy the contents of the `.php` file. Or import the json in Fluent Snippets.
3. Paste it into your WordPress snippet manager (such as Fluent Snippets, WPCode, or Code Snippets).
4. **Important:** Set the snippet to run **Everywhere**. It must be active on the frontend to detect when a customer actually completes the checkout process.
5. Navigate to the new settings page in your WordPress admin dashboard, paste your Webhook URL/s into the configuration fields, and save.

<img width="1718" height="1470" alt="image" src="https://github.com/user-attachments/assets/c6805e7e-7a05-416c-aa26-a1dff9314722" />
