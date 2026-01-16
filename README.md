# Metin2 Web Panel

Metin2 PvP sunucuları için modern web panel sistemi.

## Özellikler

- 🔐 **Güvenli Giriş** - MySQL PASSWORD, SHA1, MD5 desteği
- 📊 **Dashboard** - "Bugün Ne Yapmalıyım?" paneli
- 🧪 **Biyolog Takibi** - Teslimat süreleri
- ⚔️ **Zindan Durumu** - Cooldown takibi
- 🏪 **Pazar Durumu** - Offline shop entegrasyonu
- 🔥 **Etkinlikler** - Aktif/yaklaşan etkinlikler

## Kurulum

### 1. API Bridge (Sunucuya)

1. `api-bridge/` klasörünü web dizinine kopyala
2. 777 izni ver: `chmod -R 777 api-bridge`
3. Test: `http://SERVER_IP/api-bridge/?action=status`

### 2. Web Panel (Bilgisayarda)

```bash
cd web-panel
npm install
npm run dev
```

## API Endpoints

| Endpoint | Auth | Açıklama |
|----------|------|----------|
| `?action=status` | ❌ | Agent durumu |
| `?action=login` | ❌ | Giriş |
| `?action=dashboard` | ✅ | Ana panel |
| `?action=characters` | ✅ | Karakterler |
| `?action=shop` | ✅ | Pazar |
| `?action=events` | ✅ | Etkinlikler |

## Dosya Yapısı

```
api-bridge/
├── index.php
├── config.php
├── core/
│   ├── DatabaseManager.php
│   ├── ConfigDiscovery.php
│   └── Response.php
├── auth/
│   └── AuthManager.php
└── services/
    ├── CharacterService.php
    ├── QuestService.php
    ├── ShopService.php
    ├── EventService.php
    ├── MessageService.php
    └── DashboardService.php

web-panel/
├── src/
│   ├── app/
│   │   ├── login/
│   │   └── dashboard/
│   ├── services/
│   │   └── api.ts
│   └── contexts/
│       └── AuthContext.tsx
└── .env.local
```
