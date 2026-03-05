<template>
  <GuestLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 to-slate-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <PageHeader title="Terms and Conditions" />

        <!-- Content -->
        <ContentContainer>
          <!-- 1. Agreement to Terms -->
          <LegalSection number="1" title="Agreement to Terms" :divider="true">
            <p>These Terms and Conditions ("Terms") constitute a legally binding agreement made between you, whether personally or on behalf of an entity ("you") and Golden Multi Indotama ("Company," "we," "us," or "our"), concerning your access to and use of the ColdKey application and all related applications, sites, and services (the "Services").</p>
            <p class="mt-3">You agree that by accessing the Services, you have read, understood, and agree to be bound by all of these Terms and Conditions. If you do not agree with our Terms and Conditions, then you are expressly prohibited from using the Services and you must immediately cease use.</p>
          </LegalSection>

          <!-- 2. User Representations -->
          <LegalSection number="2" title="User Representations" :divider="true">
            <p>By using the Services, you represent and warrant that:</p>
            <ul class="list-disc list-inside mt-3 space-y-2">
              <li>You have the legal capacity and you agree to comply with these Terms and Conditions</li>
              <li>You are not a minor in the jurisdiction in which you reside</li>
              <li>You will not access the Services through automated or non-human means</li>
              <li>You will not use the Services for any illegal or unauthorized purpose</li>
              <li>Your use of the Services will not violate any applicable law or regulation</li>
            </ul>
          </LegalSection>

          <!-- 3. User Registration -->
          <LegalSection number="3" title="User Registration" :divider="true">
            <p>If you create an account on the Services, you must provide information that is accurate, complete, and current at all times. Account information must not be used for any unlawful or unauthorized purpose. You are responsible for safeguarding the password and for all activities that occur under your account.</p>
          </LegalSection>

          <!-- 4. Prohibited Activities -->
          <LegalSection number="4" title="Prohibited Activities" :divider="true">
            <p>You may not access or use the Services for any purpose other than that for which we make the Services available. The Services may not be used in connection with any commercial endeavors except those specifically endorsed or approved by us.</p>
            <p class="mt-3">As a user of the Services, you agree not to:</p>
            <ul class="list-disc list-inside mt-3 space-y-2">
              <li>Systematically retrieve data or other content from the Services to create or compile a collection, compilation, database, or directory</li>
              <li>Make any unauthorized use of the Services</li>
              <li>Sell or otherwise transfer your profile</li>
              <li>Harass, abuse, or harm another person</li>
              <li>Violate any laws, rules, or regulations applicable to the Services</li>
            </ul>
          </LegalSection>

          <!-- 5. User Generated Contributions -->
          <LegalSection number="5" title="User Generated Contributions" :divider="true">
            <p>The Services may invite you to chat, contribute to, or participate in blogs, message boards, online forums, and other functionality, and may provide you with the opportunity to create, submit, post, display, transmit, perform, publish, distribute, or broadcast content and materials to us or on the Services, including but not limited to text, writings, video, audio, photographs, graphics, comments, suggestions, or personal information or other material (collectively, "Contributions").</p>
          </LegalSection>

          <!-- 6. Contribution License -->
          <LegalSection number="6" title="Contribution License" :divider="true">
            <p>By posting your Contributions to any part of the Services, you automatically grant, and you represent and warrant that you have the right to grant, to us an unrestricted, unlimited, irrevocable, perpetual, non-exclusive, transferable, royalty-free, fully-paid, worldwide right, and license to host, use, copy, reproduce, disclose, sell, resell, publish, broadcast, retitle, archive, store, cache, publicly perform, publicly display, reformat, translate, transmit, excerpt, and distribute such Contributions (including, without limitation, your image and voice) for any purpose, known or unknown, in any form, media, or technology now known or later developed, and without compensation, obligation, accountability, or recourse to you.</p>
          </LegalSection>

          <!-- 7. Limitation of Liability -->
          <LegalSection number="7" title="Limitation of Liability" :divider="true">
            <p>In no event will the Company or its suppliers, owners, or providers of content be liable to you or any third party for any direct, indirect, incidental, special, or consequential damages arising out of or relating to these Terms and your access to and use of the Services, even if we have been advised of the possibility of such damages.</p>
          </LegalSection>

          <!-- 8. Indemnification -->
          <LegalSection number="8" title="Indemnification" :divider="true">
            <p>You agree to defend, indemnify, and hold harmless the Company and its licensee and licensors, and their employees, contractors, agents, officers and directors, from and against any and all claims, damages, obligations, losses, liabilities, costs or debt, and expenses (including but not limited to attorney's fees).</p>
          </LegalSection>

          <!-- 9. Modifications -->
          <LegalSection number="9" title="Modifications and Interruptions" :divider="true">
            <p>We reserve the right to modify or discontinue, temporarily or permanently, the Services or any features contained therein without liability to you. We will not be liable to you or any third party should we exercise that right. We may also impose limits on certain features and services or restrict your access to parts or all of the Services without notice or liability.</p>
          </LegalSection>

          <!-- 10. Termination -->
          <LegalSection number="10" title="Term and Termination" :divider="true">
            <p>These Terms and Conditions shall remain in full force and effect while you use the Services. Without limiting any other provision of these Terms and Conditions, we reserve the right to, in our sole discretion and without notice or liability, deny access to and use of the Services (including blocking certain IP addresses) to any person for any reason or for no reason, including but not limited to breach of any representation, warranty, or covenant contained herein or of any applicable law or regulation.</p>
          </LegalSection>

          <!-- 11. Contact -->
          <LegalSection number="11" title="Contact Us" :divider="false">
            <p>If you have any questions about these Terms and Conditions, please contact us at:</p>
            <div class="mt-3 text-indigo-400">
              <p>Golden Multi Indotama</p>
              <p>Email: support@goldenmulti.com</p>
              <p>Address: Jakarta, Indonesia</p>
            </div>
          </LegalSection>
        </ContentContainer>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ContentContainer from '@/Components/ContentContainer.vue';
import LegalSection from '@/Components/LegalSection.vue';
</script>
