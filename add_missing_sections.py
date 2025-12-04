#!/usr/bin/env python3
"""
Script to add missing sections to landing pages
This will add: Benefit, Preview, Testimonials, and Pricing sections
"""
import re
from pathlib import Path

# Template sections
BENEFIT_SECTION = '''    <!-- Benefits Section -->
    <section id="benefits" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Keunggulan Kami</h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Mengapa memilih layanan kami? Berikut keunggulan yang kami tawarkan.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-6 rounded-xl bg-gray-50 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Profesional</h3>
                    <p class="text-gray-600">Tim berpengalaman dan terpercaya dalam memberikan layanan terbaik.</p>
                </div>
                <div class="p-6 rounded-xl bg-gray-50 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Cepat & Efisien</h3>
                    <p class="text-gray-600">Proses yang cepat tanpa mengorbankan kualitas hasil kerja.</p>
                </div>
                <div class="p-6 rounded-xl bg-gray-50 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Terpercaya</h3>
                    <p class="text-gray-600">Komitmen tinggi terhadap kepuasan dan kepercayaan pelanggan.</p>
                </div>
            </div>
        </div>
    </section>'''

PREVIEW_SECTION = '''    <!-- Preview Section -->
    <section id="preview" class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Lihat Hasil Kami</h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Galeri hasil kerja kami yang telah membantu banyak klien.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=800&auto=format&fit=crop" alt="Preview 1" class="w-full h-64 object-cover hover:scale-110 transition-transform duration-500">
                </div>
                <div class="rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all">
                    <img src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=800&auto=format&fit=crop" alt="Preview 2" class="w-full h-64 object-cover hover:scale-110 transition-transform duration-500">
                </div>
                <div class="rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all">
                    <img src="https://images.unsplash.com/photo-1556761175-b413da4baf72?q=80&w=800&auto=format&fit=crop" alt="Preview 3" class="w-full h-64 object-cover hover:scale-110 transition-transform duration-500">
                </div>
            </div>
        </div>
    </section>'''

TESTIMONIALS_SECTION = '''    <!-- Testimonials Section -->
    <section id="testimonials" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Apa Kata Mereka</h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Testimoni dari klien yang telah menggunakan layanan kami.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-xl">
                    <div class="flex text-yellow-400 mb-4">
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    </div>
                    <p class="text-gray-700 mb-4 italic">"Layanan yang sangat memuaskan! Hasilnya sesuai ekspektasi dan prosesnya cepat."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center font-bold text-gray-700">AB</div>
                        <div>
                            <h5 class="font-bold text-gray-900">Ahmad Budi</h5>
                            <p class="text-sm text-gray-600">Klien</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 p-6 rounded-xl">
                    <div class="flex text-yellow-400 mb-4">
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    </div>
                    <p class="text-gray-700 mb-4 italic">"Sangat profesional dan detail. Saya sangat puas dengan hasilnya!"</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center font-bold text-gray-700">SD</div>
                        <div>
                            <h5 class="font-bold text-gray-900">Sari Dewi</h5>
                            <p class="text-sm text-gray-600">Klien</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 p-6 rounded-xl">
                    <div class="flex text-yellow-400 mb-4">
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    </div>
                    <p class="text-gray-700 mb-4 italic">"Pelayanan sangat baik dan hasilnya melebihi ekspektasi. Highly recommended!"</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center font-bold text-gray-700">RP</div>
                        <div>
                            <h5 class="font-bold text-gray-900">Rudi Pratama</h5>
                            <p class="text-sm text-gray-600">Klien</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>'''

PRICING_SECTION = '''    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Paket & Harga</h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Pilih paket yang sesuai dengan kebutuhan Anda.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Paket Basic</h3>
                    <div class="text-3xl font-bold text-gray-900 mb-6">Rp 500.000</div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-600">Fitur dasar</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-600">Support email</span>
                        </li>
                    </ul>
                    <button class="w-full bg-gray-200 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-300 transition-colors">Pilih Paket</button>
                </div>
                <div class="bg-blue-600 text-white p-8 rounded-xl shadow-xl transform scale-105 relative">
                    <div class="absolute top-0 right-0 bg-yellow-400 text-gray-900 px-3 py-1 rounded-bl-lg rounded-tr-xl text-xs font-bold">POPULER</div>
                    <h3 class="text-xl font-bold mb-4">Paket Premium</h3>
                    <div class="text-3xl font-bold mb-6">Rp 1.000.000</div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span>Semua fitur Basic</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span>Support prioritas</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span>Update gratis</span>
                        </li>
                    </ul>
                    <button class="w-full bg-white text-blue-600 py-3 rounded-lg font-bold hover:bg-gray-100 transition-colors">Pilih Paket</button>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Paket Enterprise</h3>
                    <div class="text-3xl font-bold text-gray-900 mb-6">Rp 2.000.000</div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-600">Semua fitur Premium</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-600">Custom development</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-600">Dedicated support</span>
                        </li>
                    </ul>
                    <button class="w-full bg-gray-200 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-300 transition-colors">Pilih Paket</button>
                </div>
            </div>
        </div>
    </section>'''

def has_section(content, section_type):
    """Check if section exists"""
    patterns = {
        'benefit': r'benefit|keuntungan|fitur|feature|kelebihan',
        'preview': r'preview|gallery|galeri|tampilan|lihat.*hasil',
        'testimonials': r'testimonial|review|ulasan|kata.*mereka|kata.*klien',
        'pricing': r'pricing|harga|paket|price|tarif'
    }
    pattern = patterns.get(section_type, '')
    return bool(re.search(pattern, content, re.IGNORECASE)) if pattern else False

def find_insertion_point(content):
    """Find where to insert new sections (before footer)"""
    # Try to find footer tag
    footer_match = re.search(r'(<footer|<section[^>]*id=["\']footer)', content, re.IGNORECASE)
    if footer_match:
        return footer_match.start()
    
    # Try to find script tag before closing body
    script_match = re.search(r'(<script|</body>)', content, re.IGNORECASE)
    if script_match:
        return script_match.start()
    
    # Last resort: before closing body
    body_match = re.search(r'</body>', content, re.IGNORECASE)
    if body_match:
        return body_match.start()
    
    return len(content)

def add_missing_sections(file_path):
    """Add missing sections to HTML file"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        added = []
        
        # Check and add sections
        if not has_section(content, 'benefit'):
            insertion_point = find_insertion_point(content)
            content = content[:insertion_point] + '\n' + BENEFIT_SECTION + '\n' + content[insertion_point:]
            added.append('benefit')
        
        if not has_section(content, 'preview'):
            insertion_point = find_insertion_point(content)
            content = content[:insertion_point] + '\n' + PREVIEW_SECTION + '\n' + content[insertion_point:]
            added.append('preview')
        
        if not has_section(content, 'testimonials'):
            insertion_point = find_insertion_point(content)
            content = content[:insertion_point] + '\n' + TESTIMONIALS_SECTION + '\n' + content[insertion_point:]
            added.append('testimonials')
        
        if not has_section(content, 'pricing'):
            insertion_point = find_insertion_point(content)
            content = content[:insertion_point] + '\n' + PRICING_SECTION + '\n' + content[insertion_point:]
            added.append('pricing')
        
        if added:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✅ {file_path.name}: Added {', '.join(added)}")
            return True
        
        return False
    except Exception as e:
        print(f"❌ Error processing {file_path.name}: {str(e)}")
        return False

def main():
    """Main function"""
    lp_dir = Path('/www/wwwroot/portfolio.irvandoda.my.id/LP')
    
    if not lp_dir.exists():
        print(f"❌ Directory not found: {lp_dir}")
        return
    
    html_files = list(lp_dir.glob('*.html'))
    total = len(html_files)
    success = 0
    
    print(f"📁 Processing {total} HTML files...\n")
    
    for html_file in html_files:
        if add_missing_sections(html_file):
            success += 1
    
    print(f"\n✨ Done! Updated {success}/{total} files")

if __name__ == '__main__':
    main()

