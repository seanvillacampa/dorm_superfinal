# Dorm Management System

A comprehensive dormitory management system built with Laravel 12, featuring tenant management, billing, meter readings, laundry services, and PayMongo payment integration.

## 🚀 Quick Start

### For First-Time Setup:

1. **Run the setup script:**
   ```powershell
   .\setup.ps1
   ```

2. **Start the application:**
   ```powershell
   .\start-app.ps1
   ```

3. **Configure PayMongo webhook** (see SETUP_GUIDE.md)

### For Regular Use:

**Start:**
```powershell
.\start-app.ps1
```

**Stop:**
```powershell
.\stop-app.ps1
```

Or just press any key in the start-app window.

---

## 📖 Documentation

- **SETUP_GUIDE.md** - Complete setup instructions for new machines
- **PRESENTATION_GUIDE.md** - Guide for demonstrating the system

---

## 🔑 Default Login Credentials

### Admin Account:
- Email: `admin@dorm.test`
- Password: `password`

### Tenant Account:
- Email: `tenant1@dorm.test` (tenant1 to tenant20)
- Password: `password`

---

## 🌐 URLs

### Local Development:
- **Application:** http://127.0.0.1:8000
- **Admin Panel:** http://127.0.0.1:8000/dashboard
- **Tenant Portal:** http://127.0.0.1:8000/tenant-portal

### ngrok (for webhooks):
- **Public URL:** Shown when you run `start-app.ps1`
- **Dashboard:** http://localhost:4040

---

## ✨ Features

### Admin Panel:
- ✅ Room management with capacity tracking
- ✅ Tenant management with contract history
- ✅ Invoice generation (manual & automated)
- ✅ Payment processing (cash & PayMongo)
- ✅ Meter readings with consumption tracking
- ✅ Laundry order management
- ✅ Financial reports & analytics
- ✅ Audit logs
- ✅ Email notifications

### Tenant Portal:
- ✅ View current bill with breakdown
- ✅ Payment history
- ✅ Pay via PayMongo (GCash, Card, QR Ph)
- ✅ View contract details
- ✅ Submit laundry orders
- ✅ Deposit tracking

### PayMongo Integration:
- ✅ Multiple payment methods (GCash, Card, QR Ph)
- ✅ Webhook support for automatic payment recording
- ✅ Test mode for safe demonstration
- ✅ Payment links with tenant tracking

---

## 🧪 Testing PayMongo

1. Login as tenant (tenant1@dorm.test / password)
2. Go to "My Bill"
3. Click "Pay ₱X,XXX.XX with PayMongo"
4. Complete payment using test credentials
5. Payment automatically recorded via webhook

**Test Mode:** All payments use test keys (no real money charged)

---

## 🛠️ Troubleshooting

### Application won't start:
```powershell
# Check if MySQL is running
# Check ports 8000 and 4040 are free
.\stop-app.ps1
.\start-app.ps1
```

### Webhook not working:
1. Check ngrok is running (http://localhost:4040)
2. Verify webhook URL in PayMongo dashboard
3. Check webhook secret in `.env` matches PayMongo
4. View logs: `storage/logs/laravel.log`

### Database errors:
```powershell
php artisan migrate:fresh --seed
```

### Cache issues:
```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 📁 Project Structure

```
dorm_/
├── app/                    # Application logic
│   ├── Http/Controllers/   # Controllers
│   ├── Models/             # Database models
│   ├── Services/           # Business logic (PayMongo, etc.)
│   └── Notifications/      # Email notifications
├── database/
│   ├── migrations/         # Database schema
│   └── seeders/            # Demo data seeders
├── resources/
│   └── views/              # Blade templates
├── routes/
│   └── web.php            # Application routes
├── public/                # Public assets
├── storage/logs/          # Application logs
├── .env                   # Environment configuration
├── setup.ps1              # Automated setup script
├── start-app.ps1          # Start script
├── stop-app.ps1           # Stop script
├── SETUP_GUIDE.md         # Detailed setup guide
└── README.md              # This file
```

---

## 🔒 Security Notes

- All PayMongo keys in `.env.example` are **TEST MODE** keys
- Default passwords should be changed for production
- Never commit `.env` file to version control
- Use environment-specific configuration

---

## 📞 Support & Issues

Check these resources when troubleshooting:
- `storage/logs/laravel.log` - Application logs
- http://localhost:4040 - ngrok request inspector
- Browser Console (F12) - Frontend errors

---

## 📄 License

This project is for educational purposes.

---

**Developed for academic presentation and demonstration purposes.**
