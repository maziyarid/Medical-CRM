import React, { useState } from "react";
import axios from "axios";
import { toast } from "sonner";
import { motion, AnimatePresence } from "framer-motion";
import PageHeader from "../components/PageHeader";
import { SERVICES, CLINIC } from "../lib/content";
import { SERVICE_ICONS, IconArrow, IconCheck, IconPhone, IconClock } from "../lib/icons";
import { EASE } from "../lib/motion";

const API = `${process.env.REACT_APP_BACKEND_URL}/api`;

export default function Appointment() {
  const [step, setStep] = useState(1);
  const [done, setDone] = useState(false);
  const [loading, setLoading] = useState(false);
  const [form, setForm] = useState({ full_name: "", phone: "", national_id: "", service: "", preferred_date: "", notes: "" });
  const upd = (k, v) => setForm((f) => ({ ...f, [k]: v }));

  const next = () => {
    if (!form.service) return toast.error("لطفاً یک خدمت را انتخاب کنید.");
    setStep(2);
  };

  const submit = async (e) => {
    e.preventDefault();
    if (!form.full_name.trim() || !form.phone.trim()) return toast.error("نام و شماره تماس الزامی است.");
    if (!/^0?\d{10,11}$/.test(form.phone.replace(/\s/g, ""))) return toast.error("شماره تماس معتبر نیست.");
    setLoading(true);
    try {
      await axios.post(`${API}/appointments`, form);
      setDone(true);
      toast.success("درخواست نوبت شما ثبت شد.");
    } catch {
      toast.error("خطا در ثبت درخواست. دوباره تلاش کنید.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <PageHeader overline="رزرو نوبت" title={["فرم پذیرش", "آنلاین"]} subtitle="درخواست خود را ثبت کنید؛ همکاران ما برای هماهنگی زمان دقیق با شما تماس می‌گیرند." />

      <section className="mx-auto max-w-5xl px-5 pb-24 lg:px-10">
        <div className="grid gap-8 lg:grid-cols-12">
          <div className="lg:col-span-8">
            {done ? (
              <motion.div initial={{ opacity: 0, scale: 0.96 }} animate={{ opacity: 1, scale: 1 }} data-testid="appointment-success"
                className="rounded-3xl border border-border bg-card p-12 text-center">
                <div className="mx-auto mb-6 grid h-20 w-20 place-items-center rounded-full bg-sage text-primary"><IconCheck size={44} /></div>
                <h3 className="font-display text-3xl">درخواست شما ثبت شد</h3>
                <p className="mx-auto mt-4 max-w-md text-muted-foreground">به‌زودی برای تأیید و هماهنگی زمان نوبت با شما تماس می‌گیریم. سپاس از اعتماد شما.</p>
              </motion.div>
            ) : (
              <div className="rounded-3xl border border-border bg-card p-8">
                <div className="mb-8 flex items-center gap-3">
                  {[1, 2].map((s) => (
                    <React.Fragment key={s}>
                      <div className={`grid h-9 w-9 place-items-center rounded-full text-sm font-bold transition-colors ${step >= s ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground"}`}>{s === 1 ? "۱" : "۲"}</div>
                      {s === 1 && <div className={`h-0.5 flex-1 rounded ${step > 1 ? "bg-primary" : "bg-muted"}`} />}
                    </React.Fragment>
                  ))}
                  <span className="ms-2 text-sm text-muted-foreground">{step === 1 ? "انتخاب خدمت" : "اطلاعات تماس"}</span>
                </div>

                <AnimatePresence mode="wait">
                  {step === 1 ? (
                    <motion.div key="s1" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} transition={{ duration: 0.3, ease: EASE }}>
                      <div className="grid gap-3 sm:grid-cols-2">
                        {SERVICES.map((s) => {
                          const Icon = SERVICE_ICONS[s.icon];
                          const on = form.service === s.title;
                          return (
                            <button key={s.id} type="button" data-testid={`appt-service-${s.id}`} onClick={() => upd("service", s.title)}
                              className={`flex items-center gap-3 rounded-2xl border p-4 text-start transition-colors ${on ? "border-primary bg-sage" : "border-border hover:border-primary"}`}>
                              <span className={`grid h-11 w-11 shrink-0 place-items-center rounded-xl ${on ? "bg-primary text-primary-foreground" : "bg-muted text-primary"}`}><Icon size={24} /></span>
                              <span className="text-sm font-medium">{s.title}</span>
                            </button>
                          );
                        })}
                      </div>
                      <button type="button" onClick={next} data-testid="appt-next-btn"
                        className="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary px-8 py-4 text-sm font-medium text-primary-foreground transition-all hover:shadow-lg">
                        مرحله بعد <IconArrow size={16} />
                      </button>
                    </motion.div>
                  ) : (
                    <motion.form key="s2" onSubmit={submit} data-testid="appointment-form" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} transition={{ duration: 0.3, ease: EASE }}>
                      <div className="space-y-5">
                        <div className="grid gap-5 sm:grid-cols-2">
                          <Input label="نام و نام خانوادگی *" testid="appt-name" value={form.full_name} onChange={(v) => upd("full_name", v)} />
                          <Input label="شماره تماس *" testid="appt-phone" value={form.phone} onChange={(v) => upd("phone", v)} dir="ltr" />
                          <Input label="کد ملی" testid="appt-national" value={form.national_id} onChange={(v) => upd("national_id", v)} dir="ltr" />
                          <Input label="تاریخ ترجیحی" testid="appt-date" value={form.preferred_date} onChange={(v) => upd("preferred_date", v)} placeholder="مثلاً ۱۴۰۳/۰۵/۲۰" />
                        </div>
                        <div>
                          <label className="mb-2 block text-sm text-muted-foreground">توضیحات</label>
                          <textarea data-testid="appt-notes" rows={3} value={form.notes} onChange={(e) => upd("notes", e.target.value)}
                            className="w-full resize-none rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary" />
                        </div>
                        <div className="rounded-2xl bg-sage/60 px-4 py-3 text-sm text-primary">خدمت انتخاب‌شده: <b>{form.service}</b></div>
                        <div className="flex gap-3">
                          <button type="button" onClick={() => setStep(1)} className="rounded-full border border-border px-6 py-4 text-sm transition-colors hover:border-primary">بازگشت</button>
                          <button type="submit" disabled={loading} data-testid="appt-submit-btn"
                            className="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-primary px-8 py-4 text-sm font-medium text-primary-foreground transition-all hover:shadow-lg disabled:opacity-60">
                            {loading ? "در حال ثبت..." : <>ثبت درخواست نوبت <IconArrow size={16} /></>}
                          </button>
                        </div>
                      </div>
                    </motion.form>
                  )}
                </AnimatePresence>
              </div>
            )}
          </div>

          <aside className="lg:col-span-4">
            <div className="sticky top-28 rounded-3xl border border-border bg-foreground p-8 text-background">
              <h3 className="font-display text-xl">تماس مستقیم</h3>
              <p className="mt-3 text-sm text-background/70">در ساعات کاری می‌توانید مستقیماً تماس بگیرید.</p>
              <div className="mt-6 space-y-4">
                {CLINIC.phones.map((p) => (
                  <a key={p} href={`tel:${p}`} className="flex items-center gap-3 text-sm hover:text-primary" dir="ltr">
                    <IconPhone size={18} /> <span>{p}</span>
                  </a>
                ))}
                <div className="flex items-start gap-3 border-t border-white/10 pt-4 text-sm text-background/80">
                  <IconClock size={18} className="mt-0.5 shrink-0" /> <span>{CLINIC.hours}</span>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </section>
    </>
  );
}

function Input({ label, value, onChange, testid, dir = "rtl", placeholder = "" }) {
  return (
    <div>
      <label className="mb-2 block text-sm text-muted-foreground">{label}</label>
      <input data-testid={testid} value={value} placeholder={placeholder} dir={dir} onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary" />
    </div>
  );
}
