import { Head, usePage } from '@inertiajs/react';
import React, { useState } from 'react';

// Tipe user dari shared props Inertia
interface User {
    id: string;
    name: string;
    email: string;
}

export default function Dashboard() {
    // Ambil auth user dari page props Inertia
    const { auth } = usePage().props as any;
    const user = auth?.user as User;

    // State Alur Sewa Loker
    const [step, setStep] = useState<number>(1);
    const [lockerSize, setLockerSize] = useState<string>('');
    const [lockerPrice, setLockerPrice] = useState<number>(0);
    const [duration, setDuration] = useState<number>(0);
    const [totalPrice, setTotalPrice] = useState<number>(0);
    const [orderId, setOrderId] = useState<string>('');
    const [pickupCode, setPickupCode] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(false);
    const [loadingText, setLoadingText] = useState<string>('Memuat...');
    const [error, setError] = useState<string>('');
    const [success, setSuccess] = useState<string>('');
    const [nfcTapped, setNfcTapped] = useState<boolean>(false);
    const [cardUid, setCardUid] = useState<string>('');
    const [registeredCard, setRegisteredCard] = useState<string>('');
    const [isRegisteringCard, setIsRegisteringCard] = useState<boolean>(false);
    const [tapCardUid, setTapCardUid] = useState<string>('');

    // Konfigurasi Tarif
    const sizes = [
        { name: 'Small', dimensions: '30x30x30 cm', icon: '📦', price: 5000 },
        { name: 'Large', dimensions: '50x50x50 cm', icon: '📦📦', price: 10000 },
    ];

    const durations = [1, 2, 3, 4, 6, 12];

    const selectLocker = (size: string, price: number) => {
        setLockerSize(size);
        setLockerPrice(price);
        setDuration(0);
        setTotalPrice(0);
        setStep(2);
    };

    const selectDuration = (d: number) => {
        setDuration(d);
        setTotalPrice(lockerPrice * d);
    };

    const handleNextToSummary = () => {
        if (duration === 0) {
            setError('Silakan pilih durasi sewa loker terlebih dahulu.');
            return;
        }
        setError('');
        setStep(3);
    };

    const handleBack = () => {
        if (step > 1) {
            setError('');
            setStep(step - 1);
        }
    };

    const resetFlow = () => {
        setLockerSize('');
        setLockerPrice(0);
        setDuration(0);
        setTotalPrice(0);
        setOrderId('');
        setPickupCode('');
        setNfcTapped(false);
        setCardUid('');
        setRegisteredCard('');
        setTapCardUid('');
        setError('');
        setSuccess('');
        setStep(1);
    };

    // Fungsi Trigger Pembayaran Midtrans Snap
    const proceedToPayment = async () => {
        setLoading(true);
        setLoadingText('Menghubungi server pembayaran...');
        setError('');

        try {
            // Ambil csrf token dari meta tag (diperlukan Laravel untuk request POST non-Inertia)
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/api/create-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    lockerSize,
                    duration,
                    price: lockerPrice,
                }),
            });

            const result = await response.json();
            setLoading(false);

            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Gagal membuat transaksi');
            }

            const currentOrderId = result.orderId;
            setOrderId(currentOrderId);

            // Periksa jika respons merupakan Mock/Dummy (misal Server Key belum di-config)
            if (result.isMock) {
                setSuccess('Mode Uji Coba: Pembayaran disimulasikan sukses!');
                setTimeout(() => {
                    handlePaymentSuccess(currentOrderId);
                }, 1000);
                return;
            }

            // Panggil popup Midtrans Snap bawaan
            if (typeof (window as any).snap !== 'undefined') {
                (window as any).snap.pay(result.token, {
                    onSuccess: function (snapResult: any) {
                        handlePaymentSuccess(currentOrderId);
                    },
                    onPending: function (snapResult: any) {
                        setError('Pembayaran tertunda. Silakan selesaikan pembayaran.');
                    },
                    onError: function (snapResult: any) {
                        setError('Pembayaran gagal. Silakan coba lagi.');
                    },
                    onClose: function () {
                        console.log('Popup pembayaran ditutup oleh user.');
                    }
                });
            } else {
                throw new Error('Midtrans Snap SDK tidak termuat dengan benar.');
            }

        } catch (err: any) {
            setLoading(false);
            setError('Gagal memulai pembayaran: ' + err.message);
        }
    };

    // Callback saat pembayaran sukses
    const handlePaymentSuccess = async (currentOrderId: string) => {
        setLoading(true);
        setLoadingText('Memverifikasi pembayaran & mengaktifkan loker...');
        setSuccess('');
        setStep(4);

        try {
            const response = await fetch(`/api/pickup/${currentOrderId}`);
            const result = await response.json();
            setLoading(false);

            if (result.success) {
                setPickupCode(result.pickup_code);
                if (result.card_uid) {
                    setRegisteredCard(result.card_uid);
                    setTapCardUid(result.card_uid);
                }
            }
        } catch (err) {
            setLoading(false);
            console.log('Gagal mengambil kode pickup:', err);
            // Tetap pasang kode default jika gagal koneksi
            setPickupCode('LCK-' + Math.floor(1000 + Math.random() * 9000));
        }
    };

    const registerCard = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!cardUid.trim()) {
            setError('Card ID RFID tidak boleh kosong.');
            return;
        }

        setIsRegisteringCard(true);
        setError('');
        setSuccess('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/api/register-card', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    orderId,
                    cardUid,
                }),
            });

            const result = await response.json();
            setIsRegisteringCard(false);

            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Gagal mendaftarkan kartu.');
            }

            setRegisteredCard(result.card_uid);
            setTapCardUid(result.card_uid); // Autofill in simulation
            setSuccess('Kartu RFID berhasil didaftarkan!');
        } catch (err: any) {
            setIsRegisteringCard(false);
            setError(err.message || 'Gagal mendaftarkan kartu.');
        }
    };

    const simulateNfcTap = async (e: React.FormEvent) => {
        e.preventDefault();
        const uidToTap = tapCardUid.trim() || registeredCard || '1A2B3C4D';
        if (!uidToTap) {
            setError('Masukkan Card ID RFID yang akan ditap.');
            return;
        }

        setLoading(true);
        setLoadingText('Mengirim sinyal tap RFID ke server...');
        setError('');
        setSuccess('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/api/arduino/tap-card', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    uid: uidToTap,
                    ldr_value: 350,
                }),
            });

            const result = await response.json();
            setLoading(false);

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal memproses tap RFID.');
            }

            if (result.unlock) {
                setNfcTapped(true);
                setSuccess(result.message || 'Akses disetujui. Pintu loker terbuka!');
            } else {
                setError(result.message || 'Akses ditolak.');
            }
        } catch (err: any) {
            setLoading(false);
            setError('Gagal mensimulasikan tap RFID: ' + err.message);
        }
    };

    return (
        <>
            <Head title="Penyewaan Smart Locker" />

            <div className="mx-auto max-w-lg p-6">
                {/* Header */}
                <div className="mb-8 text-center">
                    <h1 className="text-3xl font-bold text-indigo-600 dark:text-indigo-400">🏧 Smart Locker</h1>
                    <p className="mt-2 text-sm text-muted-foreground">Sistem Sewa Loker Pintar & Pembayaran IoT</p>
                </div>

                {/* Loading Indicator */}
                {loading && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                        <div className="rounded-2xl bg-background p-8 text-center shadow-xl border">
                            <div className="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-muted border-t-indigo-600"></div>
                            <p className="font-medium text-foreground">{loadingText}</p>
                        </div>
                    </div>
                )}

                {/* Info Profil User */}
                {user && (
                    <div className="mb-6 rounded-2xl border bg-card p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-600">
                                👤
                            </div>
                            <div>
                                <h3 className="font-bold text-sm text-foreground">{user.name}</h3>
                                <p className="text-xs text-muted-foreground">{user.email}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Status Errors */}
                {error && (
                    <div className="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                        <div className="flex items-start gap-3">
                            <span className="text-red-500">⚠️</span>
                            <div className="flex-1">
                                <h4 className="font-bold text-sm text-red-800 dark:text-red-300">Terjadi Masalah</h4>
                                <p className="mt-1 text-xs text-red-700 dark:text-red-400">{error}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Status Success */}
                {success && (
                    <div className="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950">
                        <div className="flex items-start gap-3">
                            <span className="text-green-500">✅</span>
                            <div className="flex-1">
                                <h4 className="font-bold text-sm text-green-800 dark:text-green-300">Sukses</h4>
                                <p className="mt-1 text-xs text-green-700 dark:text-green-400">{success}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* STEP 1: PILIH UKURAN LOKER */}
                {step === 1 && (
                    <div className="rounded-2xl border bg-card p-6 shadow-sm">
                        <div className="mb-5 flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-sm">1</div>
                            <h2 className="text-lg font-bold text-foreground">Pilih Ukuran Loker</h2>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            {sizes.map((s) => (
                                <button
                                    key={s.name}
                                    onClick={() => selectLocker(s.name, s.price)}
                                    className="flex flex-col items-center justify-center rounded-xl border-2 border-border p-5 hover:border-indigo-600 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition-all duration-200"
                                >
                                    <div className="text-4xl mb-3">{s.icon}</div>
                                    <span className="font-bold text-sm text-foreground">{s.name}</span>
                                    <span className="text-xs text-muted-foreground mt-1">{s.dimensions}</span>
                                    <span className="mt-3 font-extrabold text-sm text-indigo-600 dark:text-indigo-400">
                                        Rp {s.price.toLocaleString('id-ID')}/jam
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {/* STEP 2: PILIH DURASI */}
                {step === 2 && (
                    <div className="rounded-2xl border bg-card p-6 shadow-sm">
                        <div className="mb-5 flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-sm">2</div>
                            <h2 className="text-lg font-bold text-foreground">Pilih Durasi Sewa</h2>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            {durations.map((d) => (
                                <button
                                    key={d}
                                    onClick={() => selectDuration(d)}
                                    className={`rounded-lg border-2 py-3 transition-all ${
                                        duration === d
                                            ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30'
                                            : 'border-border hover:border-indigo-600'
                                    }`}
                                >
                                    <span className="block font-bold text-sm text-foreground">{d} Jam</span>
                                    <span className="text-[10px] text-muted-foreground mt-1">
                                        Rp {(lockerPrice * d).toLocaleString('id-ID')}
                                    </span>
                                </button>
                            ))}
                        </div>

                        <div className="mt-6 rounded-lg bg-muted p-4">
                            <p className="text-xs text-muted-foreground">
                                Loker: <span className="font-bold text-indigo-600">{lockerSize} (Rp {lockerPrice.toLocaleString('id-ID')}/jam)</span>
                            </p>
                            <p className="text-xs text-muted-foreground mt-1">
                                Durasi: <span className="font-bold text-indigo-600">{duration > 0 ? `${duration} Jam` : '-'}</span>
                            </p>
                            <hr className="my-2 border-muted-foreground/20" />
                            <p className="text-base font-extrabold text-foreground">
                                Total: <span className="text-indigo-600 dark:text-indigo-400">Rp {totalPrice.toLocaleString('id-ID')}</span>
                            </p>
                        </div>

                        <div className="mt-6 flex gap-3">
                            <button
                                onClick={handleBack}
                                className="flex-1 rounded-lg border py-3 font-semibold text-sm hover:bg-muted transition"
                            >
                                ← Kembali
                            </button>
                            <button
                                onClick={handleNextToSummary}
                                disabled={duration === 0}
                                className="flex-1 rounded-lg bg-indigo-600 py-3 font-semibold text-sm text-white hover:bg-indigo-700 transition disabled:opacity-50"
                            >
                                Lanjut →
                            </button>
                        </div>
                    </div>
                )}

                {/* STEP 3: KONFIRMASI PEMBAYARAN */}
                {step === 3 && (
                    <div className="rounded-2xl border bg-card p-6 shadow-sm">
                        <div className="mb-5 flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-sm">3</div>
                            <h2 className="text-lg font-bold text-foreground">Konfirmasi Pesanan</h2>
                        </div>

                        <div className="rounded-lg bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900 p-4 mb-4">
                            <h3 className="font-bold text-xs text-foreground mb-3 uppercase tracking-wider">Ringkasan Loker:</h3>
                            <div className="space-y-2 text-xs">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Ukuran Loker:</span>
                                    <span className="font-medium text-foreground">{lockerSize}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Durasi:</span>
                                    <span className="font-medium text-foreground">{duration} Jam</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Harga per Jam:</span>
                                    <span className="font-medium text-foreground">Rp {lockerPrice.toLocaleString('id-ID')}</span>
                                </div>
                                <hr className="my-2 border-indigo-200/40" />
                                <div className="flex justify-between text-base font-extrabold">
                                    <span className="text-foreground">Total Bayar:</span>
                                    <span className="text-indigo-600 dark:text-indigo-400">Rp {totalPrice.toLocaleString('id-ID')}</span>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-lg border bg-muted/30 p-4 mb-5">
                            <h3 className="font-bold text-xs text-foreground mb-2 uppercase tracking-wider">Data Customer:</h3>
                            <div className="text-xs space-y-1 text-muted-foreground">
                                <p>Nama: <span className="font-semibold text-foreground">{user.name}</span></p>
                                <p>Email: <span className="font-semibold text-foreground">{user.email}</span></p>
                            </div>
                        </div>

                        <div className="flex gap-3">
                            <button
                                onClick={handleBack}
                                className="flex-1 rounded-lg border py-3 font-semibold text-sm hover:bg-muted transition"
                            >
                                ← Kembali
                            </button>
                            <button
                                onClick={proceedToPayment}
                                className="flex-1 rounded-lg bg-green-600 py-3 font-semibold text-sm text-white hover:bg-green-700 transition"
                            >
                                Bayar Sekarang 💳
                            </button>
                        </div>
                    </div>
                )}

                {/* STEP 4: PEMBAYARAN SUKSES & TAP NFC */}
                {step === 4 && (
                    <div className="rounded-2xl border bg-card p-6 shadow-sm text-center">
                        <div className="text-5xl mb-4">🎉</div>
                        <h2 className="text-2xl font-bold text-foreground">Pembayaran Berhasil!</h2>
                        <p className="text-xs text-muted-foreground mt-2">Loker Anda telah siap dialokasikan oleh sistem.</p>

                        <div className="my-5 rounded-lg bg-muted p-4">
                            <p className="text-xs text-muted-foreground">Order ID:</p>
                            <p className="font-bold text-base text-indigo-600 mt-1">{orderId}</p>
                            {pickupCode && (
                                <div className="mt-3 border-t border-muted-foreground/15 pt-2">
                                    <p className="text-xs text-muted-foreground">Kode Pengambilan:</p>
                                    <p className="font-extrabold text-lg text-emerald-600 tracking-wider mt-1">{pickupCode}</p>
                                </div>
                            )}
                        </div>

                        {/* Simulasi RFID & ESP32 Hardware */}
                        <div className="space-y-4 text-left">
                            {/* Pendaftaran Kartu RFID */}
                            <div className="rounded-xl border bg-card p-4 shadow-sm">
                                <h3 className="font-bold text-sm text-foreground flex items-center gap-2">
                                    💳 Pendaftaran Kartu RFID
                                </h3>
                                <p className="text-xs text-muted-foreground mt-1 mb-3">
                                    Daftarkan ID kartu RFID fisik atau virtual Anda untuk mengamankan akses loker ini.
                                </p>
                                
                                {registeredCard ? (
                                    <div className="flex items-center justify-between rounded-lg bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900 p-3">
                                        <div className="text-xs">
                                            <span className="text-muted-foreground">ID Kartu Terdaftar: </span>
                                            <span className="font-bold text-green-700 dark:text-green-400">{registeredCard}</span>
                                        </div>
                                        <span className="text-xs text-green-600 font-semibold flex items-center gap-1">
                                            ✓ Siap Ditap
                                        </span>
                                    </div>
                                ) : (
                                    <form onSubmit={registerCard} className="flex gap-2">
                                        <input
                                            type="text"
                                            value={cardUid}
                                            onChange={(e) => setCardUid(e.target.value)}
                                            placeholder="Masukkan Card ID (contoh: 1A2B3C4D)"
                                            className="flex-1 rounded-lg border bg-background px-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                                            disabled={isRegisteringCard}
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition"
                                            disabled={isRegisteringCard}
                                        >
                                            {isRegisteringCard ? 'Menyimpan...' : 'Daftarkan'}
                                        </button>
                                    </form>
                                )}
                            </div>

                            {/* Simulasi Sensor IoT Tap Kartu RFID */}
                            <div className={`rounded-xl border border-dashed p-4 transition-all duration-300 ${
                                nfcTapped 
                                    ? 'border-green-300 bg-green-50/50 dark:bg-green-950/20' 
                                    : 'border-indigo-300 bg-indigo-50/50 dark:bg-indigo-950/20'
                            }`}>
                                {!nfcTapped ? (
                                    <div>
                                        <h3 className="font-bold text-sm text-foreground flex items-center gap-2">
                                            📲 Simulasi Sensor IoT (Solenoid Lock)
                                        </h3>
                                        <p className="text-xs text-muted-foreground mt-1 mb-3">
                                            Simulasikan pembacaan RFID reader pada ESP32 untuk mengirim sinyal unlock ke solenoid loker.
                                        </p>
                                        
                                        <form onSubmit={simulateNfcTap} className="space-y-3">
                                            <div className="flex gap-2">
                                                <input
                                                    type="text"
                                                    value={tapCardUid}
                                                    onChange={(e) => setTapCardUid(e.target.value)}
                                                    placeholder="ID Kartu untuk Ditap (contoh: 1A2B3C4D)"
                                                    className="flex-1 rounded-lg border bg-background px-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                                                />
                                                <button
                                                    type="submit"
                                                    className="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition"
                                                >
                                                    Simulasikan Tap RFID 💳
                                                </button>
                                            </div>
                                            <div className="text-[10px] text-muted-foreground flex gap-2 justify-between">
                                                <span>Kartu Uji Coba: <code className="bg-muted px-1.5 py-0.5 rounded font-mono">1A2B3C4D</code></span>
                                                {registeredCard && (
                                                    <span>Kartu Anda: <code className="bg-muted px-1.5 py-0.5 rounded font-mono">{registeredCard}</code></span>
                                                )}
                                            </div>
                                        </form>
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center py-4 text-center">
                                        <div className="text-4xl mb-2 text-green-500 animate-pulse">🔓</div>
                                        <h4 className="font-bold text-sm text-green-800 dark:text-green-400">Solenoid Loker Terbuka!</h4>
                                        <p className="text-xs text-muted-foreground mt-1 max-w-[280px] mx-auto">
                                            Sinyal unlock sukses disimulasikan ke ESP32. Pintu loker terbuka, silakan ambil/masukkan barang.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        <button
                            onClick={resetFlow}
                            className="mt-6 w-full rounded-lg border py-2.5 font-semibold text-xs hover:bg-muted transition"
                        >
                            ← Kembali ke Beranda Sewa Loker
                        </button>
                    </div>
                )}
            </div>
        </>
    );
}
// Definisikan layout breadcrumb agar serasi dengan Dashboard template
Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Sewa Loker',
            href: '/dashboard',
        },
    ],
};
