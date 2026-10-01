import React, { useState } from "react";
import axios from "axios";
import { toast } from "sonner";
import PageHeader from "../components/PageHeader";
import { Reveal } from "../lib/motion";
import { CLINIC } from "../lib/content";
import { IconPhone, IconMap, IconClock, IconArrow } from "../lib/icons";

const API = `${process.env.REACT_APP_BACKEND_URL}/api`;

export default function Contact() {
  const [form, setForm] = useState({ name: "", phone: "", email: "", message: "" });
  const [loading, setLoading] = useState(false);
  const upd = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  const submit = async (e) => {
    e.preventDefault();
    if (!form.name.trim() || !form.phone.trim() || !form.message.trim()) {
      toast.error("لطفاً نام، شماره تماس و پیام را کامل کنید.");
      return;
    }
    setLoading(true);
    try {
      await axios.post(`${API}/contact`, form);
      toast.success("پیام شما با موفقیت ارسال شد. به‌زودی با شما تماس می‌گیریم.");
      setForm({ name: "", phone: "", email: "", message: "" });
    } catch {
      toast.error("خطا در ارسال پیام. لطفاً دوباره تلاش کنید.");
    } finally {
      setLoading(false);
    }
  };

  const info = [
    { icon: IconMap, label: "آدرس کلینیک", value: CLINIC.address },
    { icon: IconPhone, label: "تلفن تماس", value: CLINIC.phones.join(" — "), dir: "ltr" },
    { icon: IconClock, label: "ساعات کاری", value: CLINIC.hours },
  ];

  return (
    <>
      <PageHeader overline="تماس با ما" title="در ارتباط باشیم" subtitle="برای رزرو نوبت یا هر پرسشی، از راه‌های زیر با ما در تماس باشید." />

      <section className="mx-auto max-w-7xl px-5 pb-24 lg:px-10">
        <div className="grid gap-8 lg:grid-cols-2">
          <div className="space-y-4">
            {info.map((it) => (
              <Reveal key={it.label}>
                <div className="flex items-start gap-4 rounded-3xl border border-border bg-card p-6">
                  <span className="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-sage text-primary"><it.icon size={24} /></span>
                  <div>
                    <div className="text-xs font-bold tracking-widest text-brass">{it.label}</div>
                    <div className="mt-1 leading-relaxed" dir={it.dir || "rtl"}>{it.value}</div>
                  </div>
                </div>
              </Reveal>
            ))}
            <Reveal>
              <div className="overflow-hidden rounded-3xl border border-border">
                <iframe
                  title="نقشه کلینیک"
                  src="https://www.openstreetmap.org/export/embed.html?bbox=51.40%2C35.75%2C51.44%2C35.78&layer=mapnik"
                  className="h-64 w-full"
                  loading="lazy"
                />
              </div>
            </Reveal>
          </div>

          <Reveal delay={0.1}>
            <form onSubmit={submit} data-testid="contact-form" className="rounded-3xl border border-border bg-card p-8">
              <h3 className="font-display text-2xl">ارسال پیام</h3>
              <div className="mt-6 space-y-5">
                <Field label="نام و نام خانوادگی" testid="contact-name" value={form.name} onChange={upd("name")} />
                <Field label="شماره تماس" testid="contact-phone" value={form.phone} onChange={upd("phone")} dir="ltr" />
                <Field label="ایمیل (اختیاری)" testid="contact-email" value={form.email} onChange={upd("email")} dir="ltr" />
                <div>
                  <label className="mb-2 block text-sm text-muted-foreground">پیام شما</label>
                  <textarea data-testid="contact-message" value={form.message} onChange={upd("message")} rows={4}
                    className="w-full resize-none rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary" />
                </div>
                <button type="submit" disabled={loading} data-testid="contact-submit"
                  className="inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary px-8 py-4 text-sm font-medium text-primary-foreground transition-all hover:shadow-lg disabled:opacity-60">
                  {loading ? "در حال ارسال..." : <>ارسال پیام <IconArrow size={16} /></>}
                </button>
              </div>
            </form>
          </Reveal>
        </div>
      </section>
    </>
  );
}

function Field({ label, value, onChange, testid, dir = "rtl" }) {
  return (
    <div>
      <label className="mb-2 block text-sm text-muted-foreground">{label}</label>
      <input data-testid={testid} value={value} onChange={onChange} dir={dir}
        className="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary" />
    </div>
  );
}
