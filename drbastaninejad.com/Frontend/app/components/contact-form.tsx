"use client";

import { FormEvent, useState } from "react";
import { Icon } from "./icons";

export function ContactForm() {
  const [sent, setSent] = useState(false);

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSent(true);
  };

  if (sent) {
    return (
      <div className="form-success" role="status">
        <span>
          <Icon name="check" size={32} />
        </span>
        <h2>پیام شما ثبت شد</h2>
        <p>
          این نسخه فعلاً فرانت‌اند است و ارسال واقعی پس از اتصال به سامانه مطب
          فعال می‌شود.
        </p>
        <button type="button" className="button button-outline" onClick={() => setSent(false)}>
          ارسال پیام دیگر
        </button>
      </div>
    );
  }

  return (
    <form className="contact-form" onSubmit={submit}>
      <div className="form-row">
        <label>
          <span>نام و نام خانوادگی</span>
          <input name="name" required autoComplete="name" placeholder="مثلاً سارا محمدی" />
        </label>
        <label>
          <span>شماره تماس</span>
          <input
            name="phone"
            required
            inputMode="tel"
            dir="ltr"
            autoComplete="tel"
            placeholder="09xxxxxxxxx"
          />
        </label>
      </div>
      <label>
        <span>موضوع پیام</span>
        <select name="subject" defaultValue="">
          <option value="" disabled>
            یک موضوع انتخاب کنید
          </option>
          <option>مشاوره جراحی اولیه بینی</option>
          <option>مشاوره جراحی ترمیمی</option>
          <option>پیگیری پس از عمل</option>
          <option>سایر موارد</option>
        </select>
      </label>
      <label>
        <span>متن پیام</span>
        <textarea
          name="message"
          required
          rows={6}
          placeholder="پرسش یا توضیح کوتاه خود را بنویسید..."
        />
      </label>
      <label className="form-consent">
        <input type="checkbox" required />
        <span>
          موافقم اطلاعات من فقط برای پاسخ‌گویی به این درخواست استفاده شود.
        </span>
      </label>
      <button type="submit" className="button button-primary button-large form-submit">
        ارسال پیام
        <Icon name="arrow" size={18} />
      </button>
    </form>
  );
}
