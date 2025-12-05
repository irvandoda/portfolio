# Portfolio Library - React Application

Aplikasi Portfolio Library yang dibangun ulang menggunakan React.js dan Tailwind CSS dengan desain profesional, animasi penuh, dan indikator loading/progress.

## Fitur

- ✅ React.js dengan komponen modern
- ✅ Tailwind CSS untuk styling
- ✅ Animasi profesional dan smooth transitions
- ✅ Loading indicators dan progress bar
- ✅ Responsive design
- ✅ Glass morphism effects
- ✅ Gradient animations
- ✅ Filter kategori portfolio
- ✅ 100+ portfolio projects

## Struktur Proyek

```
portfolio.irvandoda.my.id/
├── src/
│   ├── components/
│   │   ├── Header.jsx
│   │   ├── Footer.jsx
│   │   ├── CategoryFilter.jsx
│   │   ├── PortfolioCard.jsx
│   │   ├── PortfolioGrid.jsx
│   │   ├── LoadingIndicator.jsx
│   │   └── ProgressBar.jsx
│   ├── data/
│   │   └── portfolios.js
│   ├── styles/
│   │   └── index.css
│   ├── App.jsx
│   └── main.jsx
├── index.html
├── package.json
├── vite.config.js
├── tailwind.config.js
└── postcss.config.js
```

## Instalasi & Setup

### 1. Install Dependencies

```bash
npm install
```

### 2. Development Mode

```bash
npm run dev
```

Aplikasi akan berjalan di `http://localhost:3000`

### 3. Build untuk Production

```bash
npm run build
```

File hasil build akan ada di folder `dist/`

### 4. Preview Production Build

```bash
npm run preview
```

## Komponen Utama

### 1. **App.jsx**
Komponen utama yang mengatur state dan logic aplikasi.

### 2. **Header.jsx**
Header dengan title animasi dan category filter.

### 3. **PortfolioGrid.jsx**
Grid container untuk menampilkan portfolio cards.

### 4. **PortfolioCard.jsx**
Card individual untuk setiap portfolio dengan hover effects.

### 5. **LoadingIndicator.jsx**
Indikator loading dengan animasi spinner.

### 6. **ProgressBar.jsx**
Progress bar untuk menunjukkan progress loading.

## Animasi & Effects

- **Fade In Animations**: Elemen muncul dengan fade in effect
- **Hover Effects**: Transform dan scale pada hover
- **Gradient Text**: Animated gradient pada title
- **Glass Morphism**: Backdrop blur effects
- **Floating Elements**: Background decorations dengan float animation
- **Progress Indicators**: Loading dengan progress bar

## Teknologi

- React 18.2.0
- Vite 5.0.8
- Tailwind CSS 3.4.0
- PostCSS & Autoprefixer

## Catatan

- File backup index.html lama tersimpan di `index.html.backup`
- Data portfolio ada di `src/data/portfolios.js`
- Semua animasi custom ada di `tailwind.config.js`

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Lisensi

© 2025 Irvandoda Portfolio. All rights reserved.
