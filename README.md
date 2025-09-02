# Window Order Form WordPress Plugin

A WordPress plugin for handling custom window orders with live previews, PDF generation, and admin management tools.

---

## 📋 Prerequisites

- WordPress **5.0** or higher  
- PHP **7.4** or higher  
- Write permissions on the `/wp-content/uploads/` directory  

---

## 🔧 Installation Steps

### 1. Upload Plugin Files (via WordPress Dashboard)
- Download the plugin ZIP file from GitHub
- In your WordPress admin panel, go to:  
  **Plugins → Add New → Upload Plugin**
- Choose the ZIP file and click **Install Now**
- After installation, click **Activate Plugin**

### 2. Manual Installation (Alternative)
- Extract the plugin ZIP file
- Upload the `window-order-form` folder to:
3. Go to **Plugins** in the WordPress admin panel  
4. Find **"Window Order Form"** and click **Activate**

---

## 🖼️ Required Image Setup

The plugin uses specific window images that must be uploaded to your media library:

1. Go to **Media → Add New** in the WordPress admin
2. Upload the following images with **exact filenames**:

- `1-window.jpg` – Image for "1 Window" style  
- `2-column.jpg` – Image for "2 Window Door Style"  
- `3-column-window.jpg` – Image for "3 Column Window" style  

---

## 🧩 Shortcode Usage

Add the form to any page or post using the shortcode:

```plaintext
[custom_window_form]


## 🛠️ Admin Access

After activation, a new "Window Orders" menu item will appear in your WordPress admin sidebar, where you can:

- View all submitted orders

- Export orders to CSV

- Delete orders

## 🌟 Plugin Features

